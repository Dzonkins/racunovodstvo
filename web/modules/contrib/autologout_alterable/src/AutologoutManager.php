<?php

namespace Drupal\autologout_alterable;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\autologout_alterable\Events\AutologoutAlterEnabledEvent;
use Drupal\autologout_alterable\Events\AutologoutEvents;
use Drupal\autologout_alterable\Events\AutologoutProfileAlterEvent;
use Drupal\autologout_alterable\Events\AutologoutSetLastActivityEvent;
use Drupal\autologout_alterable\Utility\AutologoutProfile;
use Drupal\autologout_alterable\Utility\AutologoutProfileInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Defines an AutologoutManager service.
 */
class AutologoutManager implements AutologoutManagerInterface {

  use StringTranslationTrait;

  /**
   * The config object for 'autologout_alterable.settings'.
   */
  protected ImmutableConfig $autoLogoutSettings;

  /**
   * The calculated enabled status, keyed by identifier.
   *
   * @var bool[]
   */
  protected array $calculatedEnabled = [];

  /**
   * The autologout profiles, keyed by user id.
   *
   * @var \Drupal\autologout_alterable\Utility\AutologoutProfileInterface[]
   */
  protected array $profiles = [];

  /**
   * Constructs a new AutologoutManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
   *   The redirect destination service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected UserDataInterface $userData,
    protected RouteMatchInterface $routeMatch,
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EventDispatcherInterface $eventDispatcher,
    protected SessionInterface $session,
    protected TimeInterface $time,
    protected MessengerInterface $messenger,
    protected ConfigFactoryInterface $configFactory,
    protected RedirectDestinationInterface $redirectDestination,
    protected LoggerChannelFactoryInterface $logger,
  ) {
    $this->autoLogoutSettings = $this->configFactory->get('autologout_alterable.settings');
  }

  /**
   * Get the current request time.
   *
   * @return \DateTime
   *   The current request time.
   */
  protected function getRequestTime(): \DateTime {
    return new \DateTime('@' . $this->time->getRequestTime());
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    $identifier = $this->currentUser->id() . ':' . ($this->routeMatch->getRouteName() ?? '') . ':' . Json::encode($this->routeMatch->getRawParameters());
    if (array_key_exists($identifier, $this->calculatedEnabled)) {
      return $this->calculatedEnabled[$identifier];
    }

    if (empty($this->routeMatch->getRouteName())) {
      $this->logger->get('autologout_alterable')->error('Route name is empty. It might affect autologout functionality. Please report the issue to module maintainer.');
    }

    // Immutable disabled properties.
    if ($this->currentUser->isAnonymous() || $this->autoLogoutSettings->get('enabled') === FALSE) {
      $this->calculatedEnabled[$identifier] = FALSE;
      return FALSE;
    }

    $ip_address_whitelist = array_map('trim',
      explode("\n", trim($this->autoLogoutSettings->get('whitelisted_ip_addresses') ?: ''))
    );
    $client_ip = $this->requestStack->getCurrentRequest()?->getClientIp();
    if ($client_ip && in_array($client_ip, $ip_address_whitelist)) {
      $this->calculatedEnabled[$identifier] = FALSE;
      return FALSE;
    }

    // Alterable enabled properties.
    $enabled = TRUE;
    $disabled_routes = [
      'user.logout',
      'user.logout.confirm',
    ];
    if (in_array($this->routeMatch->getRouteName(), $disabled_routes)) {
      $enabled = FALSE;
    }

    if ($enabled) {
      $disabled_path_parts = [
        'system',
      ];

      $path_parts = explode('/', $this->requestStack->getCurrentRequest()?->getPathInfo() ?? '');
      foreach ($disabled_path_parts as $disabled_path_part) {
        if (in_array($disabled_path_part, $path_parts)) {
          $enabled = FALSE;
          break;
        }
      }
    }

    $event = new AutologoutAlterEnabledEvent($enabled, $this->currentUser, $this->routeMatch, $this->requestStack->getCurrentRequest());
    $this->eventDispatcher->dispatch($event, AutologoutEvents::ALTER_ENABLED);
    $enabled = $event->isEnabled();

    $this->calculatedEnabled[$identifier] = $enabled;
    return $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutologoutRoute(): bool {
    $autologout_routes = [
      'autologout_alterable.get_autologout_profile',
      'autologout_alterable.update_autologout_profile',
    ];

    return in_array($this->routeMatch->getRouteName(), $autologout_routes);
  }

  /**
   * Get the session timeouts by role. Keyed by role id.
   *
   * @return array
   *   The session timeouts by role.
   */
  protected function getSessionTimeoutsByRole(): array {
    $roles = array_keys($this->entityTypeManager->getStorage('user_role')->loadMultiple());
    $role_timeout = [];

    // Go through roles, get timeouts for each and return as array.
    foreach ($roles as $role) {
      $role_settings = $this->configFactory->get('autologout_alterable.role.' . $role);
      if ($role_settings->get('enabled')) {
        $timeout_role = $role_settings->get('session_timeout');
        $role_timeout[$role] = $timeout_role;
      }
    }

    return $role_timeout;
  }

  /**
   * Get default session timeout for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to get default timeout for.
   *
   * @return int
   *   The default session timeout.
   */
  protected function getDefaultTimeout(AccountInterface $account): int {
    if ($account->isAnonymous()) {
      return AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE;
    }

    if ($this->autoLogoutSettings->get('use_infinite_session_for_privileged') && $account->hasPermission('autologout_alterable infinite session timeout')) {
      return AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE;
    }

    $user_timeout = $this->userData->get('autologout_alterable', $account->id(), 'session_timeout');
    if (is_numeric($user_timeout)) {
      // User timeout takes precedence.
      return $user_timeout;
    }

    $timeout = $this->autoLogoutSettings->get('session_timeout') ?? 1800;
    // Get role timeouts for user.
    if ($this->autoLogoutSettings->get('role_logout')) {
      $user_roles = $account->getRoles();
      $output = [];
      $timeouts = $this->getSessionTimeoutsByRole();
      foreach ($user_roles as $rid => $role) {
        if (isset($timeouts[$role])) {
          $output[$rid] = $timeouts[$role];
        }
      }

      // Assign the lowest/highest timeout value to be session timeout value.
      if (!empty($output)) {
        // If one of the user's roles has a unique timeout, use this.
        if ($this->autoLogoutSettings->get('role_logout_max')) {
          $timeout = max($output);
        }
        else {
          $timeout = min($output);
        }
      }
    }

    return $timeout;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastActivity(?\DateTime $last_activity = NULL): ?\DateTime {
    if (!$this->isEnabled()) {
      return NULL;
    }

    $this->clearAutoLogoutProfiles($this->currentUser->id());

    $now = $this->getRequestTime();
    $last_activity = $last_activity ?? $now;
    $current_last_activity = $this->getLastActivity() ?? $last_activity;

    // Take the latest last activity.
    $last_activity = max($last_activity, $current_last_activity);
    // But do not take future last activity.
    $last_activity = min($last_activity, $now);

    $event = new AutologoutSetLastActivityEvent($last_activity, TRUE, $this->currentUser, $this->routeMatch, $this->requestStack->getCurrentRequest(), $this->getRequestTime());
    $this->eventDispatcher->dispatch($event, AutologoutEvents::SET_LAST_ACTIVITY);

    if ($event->lastActivityShouldBeStored()) {
      $this->session->set('autologout_alterable_last_activity', $event->getLastActivity()->getTimestamp());
    }

    return $this->getLastActivity();
  }

  /**
   * Get the last activity time for current user.
   *
   * @return \DateTime|null
   *   The last activity time, if applicable.
   */
  protected function getLastActivity(): ?\DateTime {
    if (!$this->isEnabled()) {
      return NULL;
    }
    $last_activity = $this->session->get('autologout_alterable_last_activity');
    if ($last_activity) {
      return new \DateTime('@' . $last_activity);
    }
    return NULL;
  }

  /**
   * Get the static autologout profile for the current user.
   *
   * @return \Drupal\autologout_alterable\Utility\AutologoutProfileInterface
   *   The current user autologout profile.
   */
  protected function getStaticAutologoutProfile(): AutologoutProfileInterface {
    $uid = (int) $this->currentUser->id();
    if (isset($this->profiles[$uid]) && $this->profiles[$uid] instanceof AutologoutProfileInterface) {
      return $this->profiles[$uid];
    }

    $session_expiration = NULL;

    $last_activity = $this->getLastActivity();
    if ($last_activity) {
      $default_timeout = $this->getDefaultTimeout($this->currentUser);
      if ($default_timeout !== AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE) {
        $session_expiration = new \DateTime('@' . ($last_activity->getTimestamp() + $default_timeout));
      }
    }

    $redirect_url = Url::fromRoute('user.login');
    $redirect_url->setAbsolute(TRUE);
    $query = [];
    if ($this->autoLogoutSettings->get('include_destination') && !$this->isAutologoutRoute()) {
      $query = $this->redirectDestination->getAsArray();
    }
    $redirect_url->setOption('query', $query);

    $profile = new AutologoutProfile(
      $last_activity,
      $session_expiration,
      $redirect_url,
    );

    $event = new AutologoutProfileAlterEvent($profile, $this->currentUser, $this->routeMatch, $this->requestStack->getCurrentRequest(), $this->getRequestTime());
    $this->eventDispatcher->dispatch($event, AutologoutEvents::AUTOLOGOUT_PROFILE_ALTER);
    $profile = $event->getAutologoutProfile();

    // Update last activity if changed.
    if ($profile->getLastActivity() && $last_activity?->getTimestamp() !== $profile->getLastActivity()->getTimestamp()) {
      $this->session->set('autologout_alterable_last_activity', $profile->getLastActivity()->getTimestamp());
    }

    $this->profiles[$uid] = $profile;
    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoLogoutProfile(array $redirect_extra_query = []): AutologoutProfileInterface {
    $profile = $this->getStaticAutologoutProfile();
    if (!empty($redirect_extra_query)) {
      $profile = clone $profile;
      $redirect_url = $profile->getRedirectUrl();
      $query = $redirect_url->getOption('query') ?? [];
      $query += $redirect_extra_query;
      $redirect_url->setOption('query', $query);
      $profile->setRedirectUrl($redirect_url);
    }
    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function clearAutoLogoutProfiles(?int $uid = NULL): void {
    if ($uid === NULL) {
      $this->profiles = [];
      return;
    }
    unset($this->profiles[$uid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalSettings(): array {
    $destination = NULL;
    if ($this->autoLogoutSettings->get('include_destination') && !$this->isAutologoutRoute()) {
      $destination = $this->redirectDestination->getAsArray()['destination'] ?? NULL;
    }

    return [
      'ignoreUserActivity' => !!$this->autoLogoutSettings->get('ignore_user_activity'),
      'useMouseActivity' => !!$this->autoLogoutSettings->get('client_activity_mousemove'),
      'useTouchActivity' => !!$this->autoLogoutSettings->get('client_activity_touchmove'),
      'useClickActivity' => !!$this->autoLogoutSettings->get('client_activity_click'),
      'useKeydownActivity' => !!$this->autoLogoutSettings->get('client_activity_keydown'),
      'useScrollActivity' => !!$this->autoLogoutSettings->get('client_activity_scroll'),
      'showDialog' => !!$this->autoLogoutSettings->get('show_dialog'),
      'dialogLimit' => $this->autoLogoutSettings->get('dialog_limit'),
      'dialogWidth' => $this->autoLogoutSettings->get('dialog_width'),
      'countdownFormat' => Xss::filter($this->autoLogoutSettings->get('countdown_format') ?? ''),
      'dialogTitle' => Xss::filter($this->autoLogoutSettings->get('dialog_title') ?? ''),
      'dialogMessage' => Xss::filter($this->autoLogoutSettings->get('dialog_message') ?? ''),
      'dialogStayButton' => Xss::filter($this->autoLogoutSettings->get('dialog_stay_button') ?? ''),
      'dialogLogoutButton' => Xss::filter($this->autoLogoutSettings->get('dialog_logout_button') ?? ''),
      'dialogTitleNotExtendible' => Xss::filter($this->autoLogoutSettings->get('dialog_title_not_extendible') ?? ''),
      'dialogMessageNotExtendible' => Xss::filter($this->autoLogoutSettings->get('dialog_message_not_extendible') ?? ''),
      'dialogCloseButtonNotExtendible' => Xss::filter($this->autoLogoutSettings->get('dialog_close_button_not_extendible') ?? ''),
      'dialogLogoutButtonNotExtendible' => Xss::filter($this->autoLogoutSettings->get('dialog_logout_button_not_extendible') ?? ''),
      'loggedOutDialogTitle' => Xss::filter($this->autoLogoutSettings->get('logged_out_dialog_title') ?? ''),
      'loggedOutDialogMessage' => Xss::filter($this->autoLogoutSettings->get('logged_out_dialog_message') ?? ''),
      'destination' => $destination,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function makeInducedLogoutMessage(): bool {
    if ($this->autoLogoutSettings->get('enabled') === FALSE) {
      return FALSE;
    }

    $supported_types = [
      MessengerInterface::TYPE_STATUS,
      MessengerInterface::TYPE_WARNING,
      MessengerInterface::TYPE_ERROR,
    ];
    $type = $this->autoLogoutSettings->get('induced_logout_message_type') ?? MessengerInterface::TYPE_STATUS;

    if (!in_array($type, $supported_types)) {
      $type = MessengerInterface::TYPE_STATUS;
    }
    $message = $this->autoLogoutSettings->get('induced_logout_message');
    if ($message) {
      $this->messenger->addMessage(Xss::filter($message), $type);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function makeInactivityMessage(): bool {
    if ($this->autoLogoutSettings->get('enabled') === FALSE) {
      return FALSE;
    }

    $supported_types = [
      MessengerInterface::TYPE_STATUS,
      MessengerInterface::TYPE_WARNING,
      MessengerInterface::TYPE_ERROR,
    ];
    $type = $this->autoLogoutSettings->get('inactivity_message_type') ?? MessengerInterface::TYPE_WARNING;

    if (!in_array($type, $supported_types)) {
      $type = MessengerInterface::TYPE_WARNING;
    }
    $message = $this->autoLogoutSettings->get('inactivity_message');
    if ($message) {
      $this->messenger->addMessage(Xss::filter($message), $type);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function logout(bool $check_message = TRUE, array $extra_query = []): TrustedRedirectResponse {
    if ($check_message && !empty($this->autoLogoutSettings->get('inactivity_message'))) {
      $extra_query['autologout_inactive'] = 1;
    }

    $profile = $this->getAutoLogoutProfile($extra_query);
    $redirect_url = $profile->getRedirectUrl();
    if (!$this->currentUser->isAnonymous()) {
      if ($this->autoLogoutSettings->get('use_watchdog')) {
        $session_time_left = $profile->getSessionExpiresIn() === AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE
          ? 'Infinite'
          : $profile->getSessionExpiresIn();

        $this->logger->get('autologout_alterable')->info(
          'Session closed for %name by autologout alterable, (session time left: %time_left).',
          [
            '%name' => $this->currentUser->getAccountName(),
            '%time_left' => $session_time_left,
          ]
        );
      }
      user_logout();
    }

    // Create a non cacheable redirect response.
    $response = new TrustedRedirectResponse($redirect_url->toString(TRUE)->getGeneratedUrl());
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    return $response->addCacheableDependency($cache)->setMaxAge(0);
  }

}
