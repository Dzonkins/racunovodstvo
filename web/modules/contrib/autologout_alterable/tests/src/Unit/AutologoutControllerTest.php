<?php

namespace Drupal\Tests\autologout_alterable\Unit\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\autologout_alterable\AutologoutManagerInterface;
use Drupal\autologout_alterable\Controller\AutologoutController;
use Drupal\autologout_alterable\Utility\AutologoutProfile;
use Drupal\autologout_alterable\Utility\AutologoutProfileInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the AutologoutController.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\Controller\AutologoutController
 * @group autologout_alterable
 */
class AutologoutControllerTest extends TestCase {

  /**
   * The autologout manager.
   */
  protected AutologoutManagerInterface|MockObject $autologoutManager;

  /**
   * The request stack.
   */
  protected RequestStack|MockObject $requestStack;

  /**
   * The time service.
   */
  protected TimeInterface|MockObject $time;

  /**
   * The controller under test.
   */
  protected AutologoutController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->autologoutManager = $this->createMock(AutologoutManagerInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->time = $this->createMock(TimeInterface::class);

    $this->controller = new AutologoutController($this->autologoutManager, $this->requestStack, $this->time);
  }

  /**
   * Tests the getAutologoutProfile.
   *
   * Test method with sessionExpiresIn > 0.
   */
  public function testGetAutologoutProfileSessionExpiresInPositive(): void {
    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(3600);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => 3600]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);
    $this->autologoutManager->expects($this->never())->method('logout');

    $response = $this->controller->getAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => 3600,
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

  /**
   * Tests the getAutologoutProfile.
   *
   * Test method with sessionExpiresIn < 0.
   */
  public function testGetAutologoutProfileSessionExpiresInNegative(): void {
    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(-1);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => -1]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);

    $redirect_response = $this->createMock(TrustedRedirectResponse::class);
    $redirect_response->method('getTargetUrl')->willReturn('https://example.com');
    $this->autologoutManager->expects($this->once())->method('logout')->willReturn($redirect_response);

    $response = $this->controller->getAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => -1,
      'redirectUrl' => 'https://example.com',
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

  /**
   * Tests the getAutologoutProfile method.
   *
   * Test with sessionExpiresIn EXPIRES_IN_NOT_APPLICABLE.
   */
  public function testGetAutologoutProfileSessionExpiresInNotApplicable(): void {
    $profile = $this->createMock(AutologoutProfileInterface::class);
    $profile->method('getSessionExpiresIn')->willReturn(AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);
    $this->autologoutManager->expects($this->never())->method('logout');

    $response = $this->controller->getAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => 9007199254740991,
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

  /**
   * Tests the updateAutologoutProfile.
   *
   * Test method with lastActiveAgo post data.
   */
  public function testUpdateAutologoutProfileWithLastActiveAgo(): void {
    $request = $this->createMock(Request::class);
    $request->method('getContent')->willReturn(json_encode(['lastActiveAgo' => 3600]));
    $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

    $this->time->expects($this->any())->method('getRequestTime')->willReturn(time());

    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(3601);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => 3601]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);
    $expected_last_active = new \DateTime('@' . ($this->time->getRequestTime() - 3600));
    $this->autologoutManager->expects($this->once())->method('setLastActivity')->with($expected_last_active);

    $response = $this->controller->updateAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => 3601,
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

  /**
   * Tests the updateAutologoutProfile.
   *
   * Test method with forceLogout post data.
   */
  public function testUpdateAutologoutProfileWithForceLogout(): void {
    $request = $this->createMock(Request::class);
    $request->method('getContent')->willReturn(json_encode(['forceLogout' => TRUE]));
    $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(3600);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => 3600]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);

    $redirect_response = $this->createMock(TrustedRedirectResponse::class);
    $redirect_response->method('getTargetUrl')->willReturn('https://example.com');
    $this->autologoutManager->expects($this->once())->method('logout')->willReturn($redirect_response);

    $response = $this->controller->updateAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => 0,
      'redirectUrl' => 'https://example.com',
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

  /**
   * Tests the updateAutologoutProfile.
   *
   * Test method with sessionExpiresIn < 0.
   */
  public function testUpdateAutologoutProfileSessionExpiresInNegative(): void {
    $request = $this->createMock(Request::class);
    $request->method('getContent')->willReturn(json_encode([]));
    $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

    $profile = $this->createMock(AutologoutProfile::class);
    $profile->method('getSessionExpiresIn')->willReturn(-1);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => -1]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);

    $redirect_response = $this->createMock(TrustedRedirectResponse::class);
    $redirect_response->method('getTargetUrl')->willReturn('https://example.com');

    $this->autologoutManager->expects($this->once())->method('logout')->willReturn($redirect_response);

    $response = $this->controller->updateAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => -1,
      'redirectUrl' => 'https://example.com',
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

  /**
   * Tests the updateAutologoutProfile.
   *
   * Test method with sessionExpiresIn EXPIRES_IN_NOT_APPLICABLE.
   */
  public function testUpdateAutologoutProfileSessionExpiresInNotApplicable(): void {
    $request = $this->createMock(Request::class);
    $request->method('getContent')->willReturn(json_encode([]));
    $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

    $profile = $this->createMock(AutologoutProfileInterface::class);
    $profile->method('getSessionExpiresIn')->willReturn(AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE);
    $profile->method('toArray')->willReturn(['sessionExpiresIn' => AutologoutProfileInterface::EXPIRES_IN_NOT_APPLICABLE]);

    $this->autologoutManager->expects($this->once())->method('getAutoLogoutProfile')->willReturn($profile);

    $response = $this->controller->updateAutologoutProfile();
    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals([
      'sessionExpiresIn' => 9007199254740991,
    ], json_decode($response->getContent(), TRUE));
    $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
  }

}
