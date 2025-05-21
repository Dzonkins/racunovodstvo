<?php

namespace Drupal\Tests\autologout_alterable\Unit\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\autologout_alterable\AutologoutManagerInterface;
use Drupal\autologout_alterable\EventSubscriber\AutologoutSubscriber;
use Drupal\autologout_alterable\Utility\AutologoutProfile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Tests the AutologoutSubscriber.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\EventSubscriber\AutologoutSubscriber
 * @group autologout_alterable
 */
class AutologoutSubscriberTest extends TestCase {

  /**
   * The autologout manager.
   */
  protected AutologoutManagerInterface|MockObject $autologoutManager;

  /**
   * The current user.
   */
  protected AccountInterface|MockObject $currentUser;

  /**
   * The request stack.
   */
  protected RequestStack|MockObject $requestStack;

  /**
   * The request event.
   */
  protected RequestEvent|MockObject $requestEvent;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->autologoutManager = $this->createMock(AutologoutManagerInterface::class);
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestEvent = $this->createMock(RequestEvent::class);
  }

  /**
   * Get the subscriber to test.
   *
   * @return \Drupal\autologout_alterable\EventSubscriber\AutologoutSubscriber
   *   The subscriber.
   */
  protected function getSubscriber(): AutologoutSubscriber {
    return new AutologoutSubscriber($this->autologoutManager, $this->currentUser, $this->requestStack);
  }

  /**
   * Tests the constructor and getters.
   */
  public function testConstructorAndGetters(): void {
    $subscriber = $this->getSubscriber();

    $this->assertInstanceOf(AutologoutSubscriber::class, $subscriber);
  }

  /**
   * Tests the onRequest method for anonymous user with induced logout message.
   */
  public function testOnRequestAnonymousUserWithInducedLogoutMessage(): void {
    $request = $this->createMock(Request::class);
    $request->method('getMethod')->willReturn('GET');
    $request->query = new InputBag(['autologout_induced' => '1']);
    $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);
    $this->currentUser->expects($this->once())->method('isAnonymous')->willReturn(TRUE);
    $this->autologoutManager->expects($this->once())->method('makeInducedLogoutMessage')->willReturn(TRUE);

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method for anonymous user with inactivity message.
   */
  public function testOnRequestAnonymousUserWithInactivityMessage(): void {
    $request = $this->createMock(Request::class);
    $request->method('getMethod')->willReturn('GET');
    $request->query = new InputBag(['autologout_inactive' => '1']);
    $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);
    $this->currentUser->expects($this->once())->method('isAnonymous')->willReturn(TRUE);
    $this->autologoutManager->expects($this->once())->method('makeInactivityMessage')->willReturn(TRUE);

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method for POST request.
   */
  public function testOnRequestPostAnonymousUserWithInducedLogoutMessage(): void {
    $request = $this->createMock(Request::class);
    $request->method('getMethod')->willReturn('POST');
    $request->query = new InputBag([
      'autologout_induced' => '1',
      'autologout_inactive' => '1',
    ]);
    $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);
    $this->currentUser->expects($this->once())->method('isAnonymous')->willReturn(TRUE);
    $this->autologoutManager->expects($this->never())->method('makeInducedLogoutMessage');
    $this->autologoutManager->expects($this->never())->method('makeInactivityMessage');

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method for both query parameters set.
   */
  public function testOnRequestBothQueryParametersSet(): void {
    $request = $this->createMock(Request::class);
    $request->method('getMethod')->willReturn('GET');
    $request->query = new InputBag([
      'autologout_induced' => '1',
      'autologout_inactive' => '1',
    ]);
    $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);
    $this->currentUser->expects($this->once())->method('isAnonymous')->willReturn(TRUE);
    $this->autologoutManager->expects($this->once())->method('makeInducedLogoutMessage')->willReturn(TRUE);
    $this->autologoutManager->expects($this->never())->method('makeInactivityMessage');

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method when autologout is disabled.
   */
  public function testOnRequestAutologoutDisabled(): void {
    $this->autologoutManager->expects($this->once())->method('isEnabled')->willReturn(FALSE);
    $this->autologoutManager->expects($this->never())->method('getAutoLogoutProfile');
    $this->autologoutManager->expects($this->never())->method('setLastActivity');

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method for autologout route.
   */
  public function testOnRequestAutologoutRoute(): void {
    $this->autologoutManager->expects($this->once())->method('isEnabled')->willReturn(TRUE);
    $this->autologoutManager->expects($this->once())->method('isAutologoutRoute')->willReturn(TRUE);
    $this->autologoutManager->expects($this->never())->method('getAutoLogoutProfile');
    $this->autologoutManager->expects($this->never())->method('setLastActivity');

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method for session expiration.
   */
  public function testOnRequestSessionExpiration(): void {
    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(0);
    $this->autologoutManager->expects($this->once())->method('isEnabled')->willReturn(TRUE);
    $this->autologoutManager->expects($this->once())->method('isAutologoutRoute')->willReturn(FALSE);
    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);
    $redirect_response = $this->createMock(TrustedRedirectResponse::class);
    $this->autologoutManager->expects($this->once())->method('logout')->willReturn($redirect_response);
    $this->requestEvent->expects($this->once())->method('setResponse')->with($redirect_response);
    $this->autologoutManager->expects($this->never())->method('setLastActivity');

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

  /**
   * Tests the onRequest method for updating last activity.
   */
  public function testOnRequestUpdateLastActivity(): void {
    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(3600);
    $this->autologoutManager->expects($this->once())->method('isEnabled')->willReturn(TRUE);
    $this->autologoutManager->expects($this->once())->method('isAutologoutRoute')->willReturn(FALSE);
    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);
    $this->autologoutManager->expects($this->once())->method('setLastActivity');

    $subscriber = $this->getSubscriber();
    $subscriber->onRequest($this->requestEvent);
  }

}
