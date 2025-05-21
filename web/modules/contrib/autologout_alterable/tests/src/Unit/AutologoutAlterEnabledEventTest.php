<?php

namespace Drupal\Tests\autologout_alterable\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\autologout_alterable\Events\AutologoutAlterEnabledEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the AutologoutAlterEnabledEvent.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\Events\AutologoutAlterEnabledEvent
 * @group autologout_alterable
 */
class AutologoutAlterEnabledEventTest extends TestCase {

  /**
   * Container for this test.
   */
  private TaggedContainerInterface $container;

  /**
   * The current user.
   */
  protected AccountInterface|MockObject $currentUser;

  /**
   * The route match.
   */
  protected RouteMatchInterface|MockObject $routeMatch;

  /**
   * The current request.
   */
  protected Request|MockObject $currentRequest;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->currentRequest = $this->createMock(Request::class);
  }

  /**
   * Tests the constructor and getters.
   */
  public function testConstructorAndGetters(): void {
    $event = new AutologoutAlterEnabledEvent(TRUE, $this->currentUser, $this->routeMatch, $this->currentRequest);

    $this->assertTrue($event->isEnabled());
    $this->assertSame($this->currentUser, $event->getCurrentUser());
    $this->assertSame($this->routeMatch, $event->getRouteMatch());
    $this->assertSame($this->currentRequest, $event->getCurrentRequest());
  }

  /**
   * Tests the setEnabled method.
   */
  public function testSetEnabled(): void {
    $event = new AutologoutAlterEnabledEvent(FALSE, $this->currentUser, $this->routeMatch, $this->currentRequest);
    $event->setEnabled(TRUE);

    $this->assertTrue($event->isEnabled());
  }

}
