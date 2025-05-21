<?php

namespace Drupal\Tests\autologout_alterable\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\autologout_alterable\Events\AutologoutProfileAlterEvent;
use Drupal\autologout_alterable\Utility\AutologoutProfileInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the AutologoutProfileAlterEvent.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\Events\AutologoutProfileAlterEvent
 * @group autologout_alterable
 */
class AutologoutProfileAlterEventTest extends TestCase {

  /**
   * The autologout profile.
   */
  protected AutologoutProfileInterface|MockObject $autologoutProfile;

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
    $this->autologoutProfile = $this->createMock(AutologoutProfileInterface::class);
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->currentRequest = $this->createMock(Request::class);
    $this->requestTime = new \DateTime();
  }

  /**
   * Tests the constructor and getters.
   */
  public function testConstructorAndGetters(): void {
    $event = new AutologoutProfileAlterEvent($this->autologoutProfile, $this->currentUser, $this->routeMatch, $this->currentRequest, $this->requestTime);

    $this->assertSame($this->autologoutProfile, $event->getAutologoutProfile());
    $this->assertSame($this->currentUser, $event->getCurrentUser());
    $this->assertSame($this->routeMatch, $event->getRouteMatch());
    $this->assertSame($this->currentRequest, $event->getCurrentRequest());
    $this->assertSame($this->requestTime, $event->getRequestTime());
  }

  /**
   * Tests the setAutologoutProfile method.
   */
  public function testSetAutologoutProfile(): void {
    $event = new AutologoutProfileAlterEvent($this->autologoutProfile, $this->currentUser, $this->routeMatch, $this->currentRequest, $this->requestTime);
    $newProfile = $this->createMock(AutologoutProfileInterface::class);
    $event->setAutologoutProfile($newProfile);
    $this->assertSame($newProfile, $event->getAutologoutProfile());
  }

}
