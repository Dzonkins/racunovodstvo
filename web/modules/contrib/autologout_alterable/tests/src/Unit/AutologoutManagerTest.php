<?php

namespace Drupal\Tests\autologout_alterable\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\autologout_alterable\AutologoutManager;
use Drupal\autologout_alterable\AutologoutManagerInterface;
use Drupal\autologout_alterable\Events\AutologoutAlterEnabledEvent;
use Drupal\autologout_alterable\Events\AutologoutEvents;
use Drupal\autologout_alterable\Events\AutologoutProfileAlterEvent;
use Drupal\autologout_alterable\Events\AutologoutSetLastActivityEvent;
use Drupal\autologout_alterable\Utility\AutologoutProfileInterface;
use Drupal\user\UserDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Tests the AutologoutManager.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\AutologoutManager
 * @group autologout_alterable
 */
class AutologoutManagerTest extends TestCase {

  /**
   * Container for this test.
   */
  private TaggedContainerInterface $container;

  /**
   * The current user.
   */
  protected AccountInterface|MockObject $currentUser;

  /**
   * The user data service.
   */
  protected UserDataInterface|MockObject $userData;

  /**
   * The route match.
   */
  protected RouteMatchInterface|MockObject $routeMatch;

  /**
   * The request stack.
   */
  protected RequestStack|MockObject $requestStack;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * The event dispatcher.
   */
  protected EventDispatcherInterface|MockObject $eventDispatcher;

  /**
   * The session.
   */
  protected SessionInterface|MockObject $session;

  /**
   * The time service.
   */
  protected TimeInterface|MockObject $time;

  /**
   * The messenger service.
   */
  protected MessengerInterface|MockObject $messenger;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * The redirect destination service.
   */
  protected RedirectDestinationInterface|MockObject $redirectDestination;

  /**
   * The logger channel factory.
   */
  protected LoggerChannelFactoryInterface|MockObject $logger;

  /**
   * The mocked config object for 'autologout_alterable.settings' as an array.
   */
  protected array $autoLogoutSettings;

  /**
   * The mocked config object for 'autologout_alterable.role.*' as an array.
   *
   * Keyed by role name.
   */
  protected array $autoLogoutRoleSettings;

  /**
   * The mocked request object.
   */
  protected Request|null|MockObject $mockedRequest;

  /**
   * List of mocked event subscribers.
   *
   * Keyed by event name.
   *
   * @var callable[]
   */
  protected array $mockedDispatchCallbacks = [];

  /**
   * Counter to track how many times mocked user_logout was called.
   */
  public static int $userLogoutCalled = 0;

  /**
   * Create a user mock.
   *
   * @param string $type
   *   The type of user to create.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The mocked user.
   */
  protected function createUserMock(string $type): AccountInterface {
    switch ($type) {
      case 'anonymous':
        $anonymous_user = $this->createMock(AccountInterface::class);
        $anonymous_user->method('id')->willReturn(0);
        $anonymous_user->method('isAnonymous')->willReturn(TRUE);
        $anonymous_user->method('getAccountName')->willReturn('anonymous');
        $anonymous_user->method('getRoles')->willReturn([]);
        return $anonymous_user;

      case 'user_with_roles':
        $user_with_roles = $this->createMock(AccountInterface::class);
        $user_with_roles->method('id')->willReturn(1234);
        $user_with_roles->method('isAnonymous')->willReturn(FALSE);
        $user_with_roles->method('getRoles')->willReturn([
          'authenticated',
          'long_session',
          'short_session',
        ]);
        return $user_with_roles;

      case 'user_with_infinite_session':
        $user_with_infinite_session = $this->createMock(AccountInterface::class);
        $user_with_infinite_session->method('id')->willReturn(12345);
        $user_with_infinite_session->method('isAnonymous')->willReturn(FALSE);
        $user_with_infinite_session->method('hasPermission')->with('autologout_alterable infinite session timeout')->willReturn(TRUE);
        $user_with_infinite_session->method('getRoles')->willReturn([
          'authenticated',
        ]);
        return $user_with_infinite_session;

      default:
        $plain_user = $this->createMock(AccountInterface::class);
        $plain_user->method('id')->willReturn(123);
        $plain_user->method('isAnonymous')->willReturn(FALSE);
        $plain_user->method('getRoles')->willReturn([
          'authenticated',
          'disabled_role',
        ]);
        return $plain_user;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->currentUser = $this->createUserMock('plain_user');
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->session = $this->createMock(SessionInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);
    $this->logger = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->routeMatch->method('getRouteName')->willReturn('test.route');
    $this->routeMatch->method('getRawParameters')->willReturn([]);

    $this->mockedRequest = $this->createMock(Request::class);
    $this->mockedRequest->method('getClientIp')->willReturn('1.2.3.4');
    $this->mockedRequest->method('getPathInfo')->willReturn('/test/path');
    $this->requestStack->method('getCurrentRequest')
      ->willReturnCallback(function () {
        return $this->mockedRequest;
      });

    $user_role_storage = $this->createMock(EntityStorageInterface::class);
    $user_role_storage->method('loadMultiple')->willReturn([
      'authenticated' => (object) [
        'id' => 'authenticated',
        'label' => 'Authenticated',
      ],
      'long_session' => (object) [
        'id' => 'long_session',
        'label' => 'Long session',
      ],
      'short_session' => (object) [
        'id' => 'short_session',
        'label' => 'Short session',
      ],
      'disabled_role' => (object) [
        'id' => 'disabled_role',
        'label' => 'Disabled role',
      ],
    ]);
    $this->entityTypeManager->method('getStorage')
      ->willReturn($user_role_storage);

    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->eventDispatcher->method('dispatch')
      ->willReturnCallback(function ($event, $event_name) {
        if (isset($this->mockedDispatchCallbacks[$event_name])) {
          call_user_func($this->mockedDispatchCallbacks[$event_name], $event);
        }
        return $event;
      });

    $mocked_session_store = [];
    $this->session->method('get')
      ->willReturnCallback(function ($key) use (&$mocked_session_store) {
        return $mocked_session_store[$key] ?? NULL;
      });
    $this->session->method('set')
      ->willReturnCallback(function ($key, $value) use (&$mocked_session_store) {
        $mocked_session_store[$key] = $value;
      });
    $this->container->set('session', $this->session);

    $this->time->method('getRequestTime')->willReturn(123456);
    $this->container->set('datetime.time', $this->time);

    $autologoutConfig = $this->createMock(ImmutableConfig::class);
    $autologoutConfig->method('get')->willReturnCallback(function ($key) {
      return $this->autoLogoutSettings[$key] ?? NULL;
    });

    $autologoutRoleConfigs = [];

    $autologoutRoleConfigs['long_session'] = $this->createMock(ImmutableConfig::class);
    $autologoutRoleConfigs['long_session']->method('get')
      ->willReturnCallback(function ($key) {
        return $this->autoLogoutRoleSettings['long_session'][$key] ?? NULL;
      });
    $autologoutRoleConfigs['short_session'] = $this->createMock(ImmutableConfig::class);
    $autologoutRoleConfigs['short_session']->method('get')
      ->willReturnCallback(function ($key) {
        return $this->autoLogoutRoleSettings['short_session'][$key] ?? NULL;
      });

    $autologoutRoleConfigs['disabled_role'] = $this->createMock(ImmutableConfig::class);
    $autologoutRoleConfigs['disabled_role']->method('get')
      ->willReturnCallback(function ($key) {
        return $this->autoLogoutRoleSettings['disabled_role'][$key] ?? NULL;
      });

    $this->configFactory->method('get')->willReturnCallback(function ($key) use ($autologoutConfig, $autologoutRoleConfigs) {
      return match ($key) {
        'autologout_alterable.settings' => $autologoutConfig,
        'autologout_alterable.role.long_session' => $autologoutRoleConfigs['long_session'],
        'autologout_alterable.role.short_session' => $autologoutRoleConfigs['short_session'],
        'autologout_alterable.role.disabled_role' => $autologoutRoleConfigs['disabled_role'],
        default => $this->createMock(ImmutableConfig::class),
      };
    });

    $this->redirectDestination->method('getAsArray')
      ->willReturn(['destination' => '/current/path']);

    $this->autoLogoutSettings = [
      'enabled' => TRUE,
      'session_timeout' => 1234,
      'max_session_timeout' => 172800,
      'ignore_user_activity' => FALSE,
      'use_individual_logout_threshold' => FALSE,
      'role_logout' => FALSE,
      'role_logout_max' => FALSE,
      'include_destination' => FALSE,
      'client_activity_mousemove' => TRUE,
      'client_activity_touchmove' => TRUE,
      'client_activity_click' => TRUE,
      'client_activity_keydown' => TRUE,
      'client_activity_scroll' => TRUE,
      'show_dialog' => TRUE,
      'dialog_limit' => 30,
      'dialog_width' => 450,
      'countdown_format' => '%hours%:%mins%:%secs%',
      'dialog_title' => 'You are about to be logged out',
      'dialog_message' => 'We are about to log you out for inactivity. If we do, you will lose any unsaved work. Do you need more time?',
      'dialog_stay_button' => 'Yes',
      'dialog_logout_button' => 'No',
      'dialog_title_not_extendible' => 'You are about to be logged out',
      'dialog_message_not_extendible' => 'Your session is about to be expired and cannot be extended. Save any unsaved work now.',
      'dialog_close_button_not_extendible' => 'Close message',
      'dialog_logout_button_not_extendible' => 'Logout now',
      'logged_out_dialog_title' => 'You have been logged out',
      'logged_out_dialog_message' => 'Please log in again or follow the link below.',
      'inactivity_message' => 'You have been logged out due to inactivity.',
      'inactivity_message_type' => 'warning',
      'induced_logout_message' => 'You have been logged out.',
      'induced_logout_message_type' => 'status',
      'use_watchdog' => TRUE,
      'whitelisted_ip_addresses' => '',
    ];

    $this->autoLogoutRoleSettings = [
      'long_session' => [
        'enabled' => TRUE,
        'session_timeout' => 3000,
      ],
      'short_session' => [
        'enabled' => TRUE,
        'session_timeout' => 120,
      ],
      'disabled_role' => [
        'enabled' => FALSE,
        'session_timeout' => NULL,
      ],
    ];

    // Mock url generator to be able to test absolute path option.
    $url_generator = $this->createMock(UrlGeneratorInterface::class);
    $url_generator
      ->method('generateFromRoute')
      ->willReturnCallback(function ($name, $parameters, $options) {
        $url = '/' . str_replace('.', '/', $name);
        if (!empty($options['absolute'])) {
          $url = 'https://example.com' . $url;
        }

        if (!empty($options['query'])) {
          $url .= '?' . UrlHelper::buildQuery($options['query']);
        }

        $return = new GeneratedUrl();
        $return->setGeneratedUrl($url);
        return $return;
      });
    $this->container->set('url_generator', $url_generator);

    $uuid = $this->createMock(UuidInterface::class);
    $uuid->method('generate')->willReturn('uuid');
    $this->container->set('uuid', $uuid);

    $this::$userLogoutCalled = 0;
  }

  /**
   * Get the service to test.
   *
   * @return \Drupal\autologout_alterable\AutologoutManagerInterface
   *   The autologout manager service.
   */
  protected function getService(): AutologoutManagerInterface {
    return new AutologoutManager(
      $this->currentUser,
      $this->userData,
      $this->routeMatch,
      $this->requestStack,
      $this->entityTypeManager,
      $this->eventDispatcher,
      $this->session,
      $this->time,
      $this->messenger,
      $this->configFactory,
      $this->redirectDestination,
      $this->logger
    );
  }

  /**
   * Test logging of route missing.
   */
  public function testLoggingOfRouteMissing(): void {
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn(NULL);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())->method('error')->with('Route name is empty. It might affect autologout functionality. Please report the issue to module maintainer.');

    $this->logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger->expects($this->once())->method('get')->with('autologout_alterable')->willReturn($logger);

    $service = $this->getService();
    $service->isEnabled();
  }

  /**
   * Test enabled with default values.
   */
  public function testIsEnabled(): void {
    $this->logger->expects($this->never())->method('get');

    $service = $this->getService();
    $this->assertTrue($service->isEnabled());
  }

  /**
   * Test disabled as anonymous.
   */
  public function testDisabledAsAnonymous(): void {
    $this->currentUser = $this->createUserMock('anonymous');
    $this->currentUser->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(TRUE);

    $service = $this->getService();
    $this->assertFalse($service->isEnabled());
  }

  /**
   * Test disabled by config.
   */
  public function testDisabledByConfig(): void {
    $this->autoLogoutSettings['enabled'] = FALSE;

    $service = $this->getService();
    $this->assertFalse($service->isEnabled());
  }

  /**
   * Test disabled by ip address.
   */
  public function testDisabledByIp(): void {
    $this->autoLogoutSettings['whitelisted_ip_addresses'] = '3.4.5.6' . PHP_EOL . '2.3.4.5' . PHP_EOL . '1.2.3.4' . PHP_EOL . '9.8.7.6';

    $service = $this->getService();
    $this->assertFalse($service->isEnabled());
  }

  /**
   * Data provider for testDisabledByRoutesAndPaths.
   *
   * @return array
   *   The test data.
   */
  public static function routesAndPathsProvider(): array {
    $data = [];
    $data['user_logout'] = ['user.logout', '/user/logout'];
    $data['user_logout_confirm'] = [
      'user.logout.confirm',
      '/user/logout/confirm',
    ];
    $data['system_path_1'] = ['system.foo', '/system/foo'];
    $data['system_path_2'] = ['system.bar', '/bar/system'];
    $data['system_path_3'] = ['system.baz', '/foo/bar/system/baz'];
    return $data;
  }

  /**
   * Test disabled by routes and paths.
   *
   * @dataProvider routesAndPathsProvider
   */
  public function testDisabledByRoutesAndPaths(string $route_name, string $path): void {
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn($route_name);
    $this->routeMatch->method('getRawParameters')->willReturn([]);

    $this->mockedRequest = $this->createMock(Request::class);
    $this->mockedRequest->method('getPathInfo')->willReturn($path);

    $service = $this->getService();
    $this->assertFalse($service->isEnabled());
  }

  /**
   * Test disabled by event subscriber.
   */
  public function testDisabledByEventSubscriber() {
    $this->mockedDispatchCallbacks[AutologoutEvents::ALTER_ENABLED] = function (AutologoutAlterEnabledEvent $event) {
      $event->setEnabled(FALSE);
    };

    $service = $this->getService();
    $this->assertFalse($service->isEnabled());
  }

  /**
   * Test enabled by event subscriber.
   */
  public function testEnabledByEventSubscriber() {
    $this->mockedRequest = $this->createMock(Request::class);
    $this->mockedRequest->method('getPathInfo')
      ->willReturn('/system/disabled-path');

    // Make sure this is disabled by default.
    $service = $this->getService();
    $this->assertFalse($service->isEnabled());

    $this->mockedDispatchCallbacks[AutologoutEvents::ALTER_ENABLED] = function (AutologoutAlterEnabledEvent $event) {
      $event->setEnabled(TRUE);
    };

    // Verify that the event subscriber can enable the service.
    $service = $this->getService();
    $this->assertTrue($service->isEnabled());
  }

  /**
   * Data provider for testIsAutologoutRoute.
   *
   * @return array
   *   The provided data.
   */
  public static function autologoutRoutesProvider(): array {
    $data = [];

    $data['is_get_profile_route'] = [
      'autologout_alterable.get_autologout_profile',
      TRUE,
    ];
    $data['is_update_profile_route'] = [
      'autologout_alterable.update_autologout_profile',
      TRUE,
    ];
    $data['is_other_route'] = ['other.route', FALSE];

    return $data;
  }

  /**
   * Test isAutologoutRoute.
   *
   * @dataProvider autologoutRoutesProvider
   */
  public function testIsAutologoutRoute(string $route_name, bool $expected): void {
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn($route_name);
    $this->routeMatch->method('getRawParameters')->willReturn([]);

    $service = $this->getService();
    $this->assertEquals($expected, $service->isAutologoutRoute());
  }

  /**
   * Test setLastActivity when disabled.
   */
  public function testSetLastActivityDisabled(): void {
    $this->autoLogoutSettings['enabled'] = FALSE;

    $service = $this->getService();
    $this->assertNull($service->setLastActivity());
  }

  /**
   * Test setLastActivity with no parameter.
   */
  public function testSetLastActivityDefault(): void {
    $service = $this->getService();
    $set_last_activity = $service->setLastActivity();

    $this->assertEquals(new \DateTime('@123456'), $set_last_activity);
  }

  /**
   * Data provider for testSetLastActivity.
   */
  public static function lastActivityProvider(): array {
    $data = [];

    $data['no_parameter'] = [NULL, new \DateTime('@123456')];

    // Future last activity still falls back to current time.
    $data['future_parameter'] = [
      new \DateTime('@456789'),
      new \DateTime('@123456'),
    ];

    // Past last activity is used.
    $data['past_parameter'] = [
      new \DateTime('@12345'),
      new \DateTime('@12345'),
    ];

    return $data;
  }

  /**
   * Test setLastActivity with parameter.
   *
   * @dataProvider lastActivityProvider
   */
  public function testSetLastActivityWithParameter(?\DateTime $last_activity_parameter, \DateTime $expected): void {
    $service = $this->getService();
    $set_last_activity = $service->setLastActivity($last_activity_parameter);

    $this->assertEquals($expected, $set_last_activity);
  }

  /**
   * Data provider for testSetLastActivityEvent.
   */
  public static function lastActivityEventProvider(): array {
    $data = [];

    $data['future_parameter'] = [
      new \DateTime('@456789'),
      TRUE,
      new \DateTime('@456789'),
    ];
    $data['past_parameter'] = [
      new \DateTime('@12345'),
      TRUE,
      new \DateTime('@12345'),
    ];
    $data['past_parameter_no_store'] = [new \DateTime('@12345'), FALSE, NULL];

    return $data;
  }

  /**
   * Test setLastActivity with event subscriber.
   *
   * @dataProvider lastActivityEventProvider
   */
  public function testSetLastActivityEvent(\DateTime $last_activity_parameter, bool $store, ?\DateTime $expected): void {
    $this->mockedDispatchCallbacks[AutologoutEvents::SET_LAST_ACTIVITY] = function (AutologoutSetLastActivityEvent $event) use ($last_activity_parameter, $store) {
      $event->setLastActivity($last_activity_parameter);
      $event->setLastActivityShouldBeStored($store);
    };

    if (!$store) {
      $this->session->expects($this->never())->method('set');
    }

    $service = $this->getService();
    $set_last_activity = $service->setLastActivity();
    $this->assertEquals($expected, $set_last_activity);
  }

  /**
   * Data provider for testGetAutoLogoutProfile.
   */
  public static function autologoutProfileProvider(): array {
    $data = [];

    $plain_user = 'plain_user';
    $user_with_individual_session = 'user_with_individual_session';
    $user_with_roles = 'user_with_roles';
    $user_with_infinite_session = 'user_with_infinite_session';

    $use_event_subscriber_options = [FALSE, TRUE];
    foreach ($use_event_subscriber_options as $use_event_subscriber) {
      $expected_profile_base = [
        'lastActivity' => $use_event_subscriber
          ? (new \DateTime('@123000'))->format('c')
          : (new \DateTime('@123456'))->format('c'),
        'lastActivityAgo' => $use_event_subscriber
          ? 456
          : 0,
        'sessionExpiration' => $use_event_subscriber
          ? (new \DateTime('@' . (123000 + 1234)))->format('c')
          : (new \DateTime('@' . (123456 + 1234)))->format('c'),
        'sessionExpiresIn' => $use_event_subscriber
          ? 778
          : 1234,
        'redirectUrl' => $use_event_subscriber
          ? 'https://example.com/overridden/redirect'
          : 'https://example.com/user/login',
        'extendible' => !$use_event_subscriber,
        'id' => 'uuid',
      ];

      // User with individual set session timeout.
      $user = $user_with_individual_session;
      $setting_overrides = [
        'role_logout' => TRUE,
      ];

      $expected_profile = $expected_profile_base;
      if (!$use_event_subscriber) {
        $expected_profile['sessionExpiration'] = (new \DateTime('@' . (123456 + 500)))->format('c');
        $expected_profile['sessionExpiresIn'] = 500;
      }

      $data['individual_session_timeout_' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // User with infinite session timeout and config true.
      $user = $user_with_infinite_session;
      $setting_overrides = [
        'use_infinite_session_for_privileged' => TRUE,
      ];
      $expected_profile = $expected_profile_base;
      if (!$use_event_subscriber) {
        $expected_profile['sessionExpiration'] = NULL;
        $expected_profile['sessionExpiresIn'] = AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE;
      }

      $data['use_infinite_session_timeout_' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // User with infinite session timeout and config false.
      $user = $user_with_infinite_session;
      $setting_overrides = [
        'use_infinite_session_for_privileged' => FALSE,
      ];
      $expected_profile = $expected_profile_base;

      $data['use_infinite_session_timeout_' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // User with roles, using highest session timeout.
      $user = $user_with_roles;

      $setting_overrides = [
        'role_logout' => TRUE,
        'role_logout_max' => TRUE,
      ];

      $expected_profile = $expected_profile_base;
      if (!$use_event_subscriber) {
        $expected_profile['sessionExpiration'] = (new \DateTime('@' . (123456 + 3000)))->format('c');
        $expected_profile['sessionExpiresIn'] = 3000;
      }

      $data['user_with_roles_use_highest' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // User with roles, using lowest session timeout.
      $user = $user_with_roles;

      $setting_overrides = [
        'role_logout' => TRUE,
        'role_logout_max' => FALSE,
      ];

      $expected_profile = $expected_profile_base;
      if (!$use_event_subscriber) {
        $expected_profile['sessionExpiration'] = (new \DateTime('@' . (123456 + 120)))->format('c');
        $expected_profile['sessionExpiresIn'] = 120;
      }

      $data['user_with_roles_use_lowest' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // User with roles, disabled role_logout.
      $user = $user_with_roles;

      $setting_overrides = [
        'role_logout' => FALSE,
      ];

      $expected_profile = $expected_profile_base;
      $data['user_with_roles_disabled_role_logout' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // Plain user.
      $user = $plain_user;

      $setting_overrides = [];
      $expected_profile = $expected_profile_base;
      $data['plain_user' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // Plain user with include_destination.
      $user = $plain_user;

      $setting_overrides = [
        'include_destination' => TRUE,
      ];

      $expected_profile = $expected_profile_base;
      if (!$use_event_subscriber) {
        $expected_profile['redirectUrl'] = $expected_profile['redirectUrl'] . '?destination=/current/path';
      }

      $data['plain_user_include_destination' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];

      // Plain user with disabled config.
      $user = $plain_user;

      $setting_overrides = [
        'enabled' => FALSE,
      ];

      $expected_profile = $expected_profile_base;
      if (!$use_event_subscriber) {
        $expected_profile = [
          'lastActivity' => NULL,
          'lastActivityAgo' => NULL,
          'sessionExpiration' => NULL,
          'sessionExpiresIn' => NULL,
          'redirectUrl' => 'https://example.com/user/login',
          'extendible' => TRUE,
          'id' => 'uuid',
        ];
      }

      $data['plain_user_disabled_config' . ($use_event_subscriber ? '_with_subscriber' : '')] = [
        $user,
        $use_event_subscriber,
        $setting_overrides,
        $expected_profile,
      ];
    }

    return $data;
  }

  /**
   * Test getAutoLogoutProfile.
   *
   * @dataProvider autologoutProfileProvider
   */
  public function testGetAutoLogoutProfile(string $current_user_type, bool $subscriber, array $setting_overrides, array $expected) {
    foreach ($setting_overrides as $key => $value) {
      $this->autoLogoutSettings[$key] = $value;
    }

    $user_data = $this->createMock(UserDataInterface::class);
    if ($current_user_type === 'user_with_individual_session') {
      $user_data->method('get')
        ->willReturnCallback(function (string $module, int $uid, string $name) {
          if ($module === 'autologout_alterable' && $uid === 123 && $name === 'session_timeout') {
            return 500;
          }
          return NULL;
        });
    }
    else {
      $user_data->method('get')->willReturn(NULL);
    }

    $this->currentUser = $this->createUserMock($current_user_type);
    $this->userData = $user_data;
    $this->session = $this->createMock(SessionInterface::class);
    $this->session->method('get')->willReturn(123456);

    if ($subscriber) {
      $this->mockedDispatchCallbacks[AutologoutEvents::AUTOLOGOUT_PROFILE_ALTER] = function (AutologoutProfileAlterEvent $event) use ($expected) {
        $profile = $event->getAutologoutProfile();
        $profile->setLastActivity(new \DateTime($expected['lastActivity']));
        $profile->setSessionExpiration(new \DateTime($expected['sessionExpiration']));
        $profile->setRedirectUrl(Url::fromRoute('overridden.redirect'));
        $profile->setExtendible($expected['extendible']);
        $event->setAutologoutProfile($profile);
      };
    }

    $service = $this->getService();
    $profile = $service->getAutoLogoutProfile();

    $this->assertInstanceOf(AutologoutProfileInterface::class, $profile);
    $this->assertEquals($expected, $profile->toArray());

    unset($expected['lastActivity'], $expected['sessionExpiration']);
    $this->assertEquals($expected, $profile->toArray(TRUE));

    // Test with extra params.
    $extra_params = [
      'extra' => 'param',
    ];
    $expected['redirectUrl'] = !str_contains($expected['redirectUrl'], '?')
      ? $expected['redirectUrl'] . '?extra=param'
      : $expected['redirectUrl'] . '&extra=param';
    $profile = $service->getAutoLogoutProfile($extra_params);
    $this->assertEquals($expected, $profile->toArray(TRUE));
  }

  /**
   * Test getAutoLogoutProfile static cache and update.
   */
  public function testGetAutoLogoutProfileStaticCache() {
    // Use event dispatcher to indicate how many times the profile is altered.
    $alter_count = 0;
    $this->mockedDispatchCallbacks[AutologoutEvents::AUTOLOGOUT_PROFILE_ALTER] = function () use (&$alter_count) {
      $alter_count++;
    };

    $service = $this->getService();
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(1, $alter_count);
    $this->assertNull($profile->getLastActivity());

    // Test that the profile is not altered again.
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(1, $alter_count);
    $this->assertNull($profile->getLastActivity());

    // Clear all profiles.
    $service->clearAutoLogoutProfiles();
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(2, $alter_count);
    $this->assertNull($profile->getLastActivity());

    // Test that the profile is not altered again.
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(2, $alter_count);
    $this->assertNull($profile->getLastActivity());

    // Clear specific profile.
    $service->clearAutoLogoutProfiles(123);
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(3, $alter_count);
    $this->assertNull($profile->getLastActivity());

    // Test that the profile is not altered again.
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(3, $alter_count);
    $this->assertNull($profile->getLastActivity());

    // Test that the profile is updated when the last activity is set.
    $last_activity = $service->setLastActivity();
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(4, $alter_count);
    $this->assertNotNull($profile->getLastActivity());
    $this->assertEquals($last_activity, $profile->getLastActivity());

    // Test that the profile is not altered again.
    $profile = $service->getAutoLogoutProfile();
    $this->assertEquals(4, $alter_count);
    $this->assertNotNull($profile->getLastActivity());
    $this->assertEquals($last_activity, $profile->getLastActivity());
  }

  /**
   * Test getDrupalSettings with redirect destination.
   */
  public function testGetDrupalSettingsWithRedirect() {
    $this->autoLogoutSettings['include_destination'] = TRUE;
    $service = $this->getService();
    $settings = $service->getDrupalSettings();
    $this->assertEquals([
      'ignoreUserActivity' => FALSE,
      'useMouseActivity' => TRUE,
      'useTouchActivity' => TRUE,
      'useClickActivity' => TRUE,
      'useKeydownActivity' => TRUE,
      'useScrollActivity' => TRUE,
      'showDialog' => TRUE,
      'dialogLimit' => 30,
      'dialogWidth' => 450,
      'countdownFormat' => '%hours%:%mins%:%secs%',
      'dialogTitle' => 'You are about to be logged out',
      'dialogMessage' => 'We are about to log you out for inactivity. If we do, you will lose any unsaved work. Do you need more time?',
      'dialogStayButton' => 'Yes',
      'dialogLogoutButton' => 'No',
      'dialogTitleNotExtendible' => 'You are about to be logged out',
      'dialogMessageNotExtendible' => 'Your session is about to be expired and cannot be extended. Save any unsaved work now.',
      'dialogCloseButtonNotExtendible' => 'Close message',
      'dialogLogoutButtonNotExtendible' => 'Logout now',
      'loggedOutDialogTitle' => 'You have been logged out',
      'loggedOutDialogMessage' => 'Please log in again or follow the link below.',
      'destination' => '/current/path',

    ], $settings);
  }

  /**
   * Test getDrupalSettings without redirect destination.
   */
  public function testGetDrupalSettingsWithoutRedirect() {
    $this->autoLogoutSettings['include_destination'] = FALSE;
    $service = $this->getService();
    $settings = $service->getDrupalSettings();
    $this->assertEquals([
      'ignoreUserActivity' => FALSE,
      'useMouseActivity' => TRUE,
      'useTouchActivity' => TRUE,
      'useClickActivity' => TRUE,
      'useKeydownActivity' => TRUE,
      'useScrollActivity' => TRUE,
      'showDialog' => TRUE,
      'dialogLimit' => 30,
      'dialogWidth' => 450,
      'countdownFormat' => '%hours%:%mins%:%secs%',
      'dialogTitle' => 'You are about to be logged out',
      'dialogMessage' => 'We are about to log you out for inactivity. If we do, you will lose any unsaved work. Do you need more time?',
      'dialogStayButton' => 'Yes',
      'dialogLogoutButton' => 'No',
      'dialogTitleNotExtendible' => 'You are about to be logged out',
      'dialogMessageNotExtendible' => 'Your session is about to be expired and cannot be extended. Save any unsaved work now.',
      'dialogCloseButtonNotExtendible' => 'Close message',
      'dialogLogoutButtonNotExtendible' => 'Logout now',
      'loggedOutDialogTitle' => 'You have been logged out',
      'loggedOutDialogMessage' => 'Please log in again or follow the link below.',
      'destination' => NULL,
    ], $settings);
  }

  /**
   * Message data provider.
   *
   * @return array
   *   The provider data.
   */
  public static function messagesProvider(): array {
    $data = [];
    $types = [
      MessengerInterface::TYPE_STATUS,
      MessengerInterface::TYPE_WARNING,
      MessengerInterface::TYPE_ERROR,
      'unsupported',
    ];
    $use_message = [TRUE, FALSE];
    foreach ($types as $type) {
      foreach ($use_message as $use) {
        $message = $use ? 'Test message' : '';
        $data[$type . '_message_' . ($use ? 'true' : 'false')] = [
          $type,
          $message,
        ];
      }
    }
    return $data;
  }

  /**
   * Test makeInducedLogoutMessage.
   *
   * @dataProvider messagesProvider
   */
  public function testMakeInducedLogoutMessage(string $type, string $message) {
    $this->autoLogoutSettings['induced_logout_message_type'] = $type;
    $this->autoLogoutSettings['induced_logout_message'] = $message;

    $expected_type = $type === 'unsupported' ? MessengerInterface::TYPE_STATUS : $type;
    $use_messenger = !!$message;

    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->messenger->expects($use_messenger ? $this->once() : $this->never())
      ->method('addMessage')
      ->with($message, $expected_type);

    $service = $this->getService();
    $delivered = $service->makeInducedLogoutMessage();
    $this->assertEquals($use_messenger, $delivered);

    // Verify no message is delivered when disabled.
    $this->autoLogoutSettings['enabled'] = FALSE;
    $delivered = $service->makeInducedLogoutMessage();
    $this->assertFalse($delivered);
  }

  /**
   * Test makeInactivityMessage.
   *
   * @dataProvider messagesProvider
   */
  public function testMakeInactivityMessage(string $type, string $message) {
    $this->autoLogoutSettings['inactivity_message_type'] = $type;
    $this->autoLogoutSettings['inactivity_message'] = $message;

    $expected_type = $type === 'unsupported' ? MessengerInterface::TYPE_WARNING : $type;
    $use_messenger = !!$message;

    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->messenger->expects($use_messenger ? $this->once() : $this->never())
      ->method('addMessage')
      ->with($message, $expected_type);

    $service = $this->getService();
    $delivered = $service->makeInactivityMessage();
    $this->assertEquals($use_messenger, $delivered);

    // Verify no message is delivered when disabled.
    $this->autoLogoutSettings['enabled'] = FALSE;
    $delivered = $service->makeInactivityMessage();
    $this->assertFalse($delivered);
  }

  /**
   * Test logout with no parameters.
   */
  public function testLogoutNoParams() {
    // Skip logger.
    $this->autoLogoutSettings['use_watchdog'] = FALSE;
    $this->logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger->expects($this->never())->method('get');

    $service = $this->getService();
    $response = $service->logout();
    $this->assertEquals(1, $this::$userLogoutCalled);
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $expected_response = new TrustedRedirectResponse('https://example.com/user/login?autologout_inactive=1');
    $expected_response->addCacheableDependency($cache);
    $expected_response->setMaxAge(0);
    $this->assertEquals($expected_response, $response);
  }

  /**
   * Test logout with parameters.
   */
  public function testLogoutWithParams() {
    // Use logger.
    $this->autoLogoutSettings['use_watchdog'] = TRUE;

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())->method('info')->with('Session closed for %name by autologout alterable, (session time left: %time_left).', [
      '%name' => $this->currentUser->getAccountName(),
      '%time_left' => 'Infinite',
    ]);

    $this->logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger->expects($this->once())->method('get')->with('autologout_alterable')->willReturn($logger);

    $service = $this->getService();
    $response = $service->logout(FALSE, ['extra' => 'param']);
    $this->assertEquals(1, $this::$userLogoutCalled);
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $expected_response = new TrustedRedirectResponse('https://example.com/user/login?extra=param');
    $expected_response->addCacheableDependency($cache);
    $expected_response->setMaxAge(0);
    $this->assertEquals($expected_response, $response);
  }

  /**
   * Test logout with parameters and no session time left.
   */
  public function testLogoutWithParamsAndNoSessionTimeLeft() {
    // Use logger.
    $this->autoLogoutSettings['use_watchdog'] = TRUE;

    $this->mockedDispatchCallbacks[AutologoutEvents::AUTOLOGOUT_PROFILE_ALTER] = function (AutologoutProfileAlterEvent $event) {
      $profile = $event->getAutologoutProfile();
      $profile->setSessionExpiration(new \DateTime('@' . (123456 + 5)));
      $event->setAutologoutProfile($profile);
    };

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())->method('info')->with('Session closed for %name by autologout alterable, (session time left: %time_left).', [
      '%name' => $this->currentUser->getAccountName(),
      '%time_left' => '5',
    ]);

    $this->logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger->expects($this->once())->method('get')->with('autologout_alterable')->willReturn($logger);

    $service = $this->getService();
    $response = $service->logout(FALSE, ['extra' => 'param']);
    $this->assertEquals(1, $this::$userLogoutCalled);
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $expected_response = new TrustedRedirectResponse('https://example.com/user/login?extra=param');
    $expected_response->addCacheableDependency($cache);
    $expected_response->setMaxAge(0);
    $this->assertEquals($expected_response, $response);
  }

  /**
   * Test logout as anonymous.
   */
  public function testLogoutAnonymous() {
    $this->autoLogoutSettings['use_watchdog'] = TRUE;
    $this->logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger->expects($this->never())->method('get');

    $this->currentUser = $this->createUserMock('anonymous');

    $service = $this->getService();
    $response = $service->logout();
    $this->assertEquals(0, $this::$userLogoutCalled);
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $expected_response = new TrustedRedirectResponse('https://example.com/user/login?autologout_inactive=1');
    $expected_response->addCacheableDependency($cache);
    $expected_response->setMaxAge(0);
    $this->assertEquals($expected_response, $response);
  }

}

namespace Drupal\autologout_alterable;

use Drupal\Tests\autologout_alterable\Unit\AutologoutManagerTest;

/**
 * Mock user_logout function.
 */
function user_logout(): void {
  AutologoutManagerTest::$userLogoutCalled++;
}
