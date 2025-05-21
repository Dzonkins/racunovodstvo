<?php

namespace Drupal\Tests\autologout_alterable\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\autologout_alterable\Events\AutologoutSetLastActivityEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the AutologoutSetLastActivityEvent.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\Events\AutologoutSetLastActivityEvent
 * @group autologout_alterable
 */
class AutologoutSetLastActivityEventTest extends TestCase {

  /**
   * The last activity.
   */
  protected \DateTime $lastActivity;

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
   * The request time.
   */
  protected \DateTime $requestTime;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lastActivity = new \DateTime();
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->currentRequest = $this->createMock(Request::class);
    $this->requestTime = new \DateTime();
  }

  /**
   * Tests the constructor and getters.
   */
  public function testConstructorAndGetters(): void {
    $event = new AutologoutSetLastActivityEvent($this->lastActivity, TRUE, $this->currentUser, $this->routeMatch, $this->currentRequest, $this->requestTime);

    $this->assertSame($this->lastActivity, $event->getLastActivity());
    $this->assertTrue($event->lastActivityShouldBeStored());
    $this->assertSame($this->currentUser, $event->getCurrentUser());
    $this->assertSame($this->routeMatch, $event->getRouteMatch());
    $this->assertSame($this->currentRequest, $event->getCurrentRequest());
    $this->assertSame($this->requestTime, $event->getRequestTime());
  }

  /**
   * Tests the setLastActivity method.
   */
  public function testSetLastActivity(): void {
    $event = new AutologoutSetLastActivityEvent($this->lastActivity, TRUE, $this->currentUser, $this->routeMatch, $this->currentRequest, $this->requestTime);
    $newActivity = new \DateTime('yesterday');
    $event->setLastActivity($newActivity);

    $this->assertSame($newActivity, $event->getLastActivity());
  }

  /**
   * Tests the setLastActivityShouldBeStored method.
   */
  public function testSetLastActivityShouldBeStored(): void {
    $event = new AutologoutSetLastActivityEvent($this->lastActivity, FALSE, $this->currentUser, $this->routeMatch, $this->currentRequest, $this->requestTime);
    $this->assertFALSE($event->lastActivityShouldBeStored());
    $event->setLastActivityShouldBeStored(TRUE);

    $this->assertTrue($event->lastActivityShouldBeStored());
  }

}
