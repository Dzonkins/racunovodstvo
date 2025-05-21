<?php

namespace Drupal\Tests\autologout_alterable\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\autologout_alterable\Utility\AutologoutProfile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\TaggedContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Tests the AutologoutProfile utility object.
 *
 * @coversDefaultClass \Drupal\autologout_alterable\Utility\AutologoutProfile
 * @group autologout_alterable
 */
class AutologoutProfileTest extends TestCase {

  /**
   * Container for this test.
   */
  private TaggedContainerInterface $container;

  /**
   * The last activity.
   */
  protected \DateTime $lastActivity;

  /**
   * The session expiration.
   */
  protected \DateTime $sessionExpiration;

  /**
   * The redirect URL.
   */
  protected Url|MockObject $redirectUrl;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->lastActivity = new \DateTime();
    $this->sessionExpiration = new \DateTime('+1 hour');
    $this->redirectUrl = $this->createMock(Url::class);
    $generated_url = $this->createMock(GeneratedUrl::class);
    $generated_url->method('getGeneratedUrl')->willReturn('https://example.com/redirect');
    $this->redirectUrl->method('toString')->with(TRUE)->willReturn($generated_url);

    $now = new \DateTime();
    $time_service = $this->createMock(TimeInterface::class);
    $time_service->method('getRequestTime')->willReturn($now->getTimestamp());
    $this->container->set('datetime.time', $time_service);

    $session = $this->createMock(SessionInterface::class);
    $session->method('get')->with('autologout_alterable_profile_id')->willReturn(NULL);
    $this->container->set('session', $session);

    $uuid = $this->createMock(UuidInterface::class);
    $uuid->method('generate')->willReturn('uuid');
    $this->container->set('uuid', $uuid);
  }

  /**
   * Tests the constructor and getters.
   */
  public function testConstructorAndGetters(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);

    $this->assertSame($this->lastActivity, $profile->getLastActivity());
    $this->assertSame($this->sessionExpiration, $profile->getSessionExpiration());
    $this->assertSame($this->redirectUrl, $profile->getRedirectUrl());
    $this->assertTrue($profile->isExtendible());
    $this->assertEquals('uuid', $profile->getId());
  }

  /**
   * Tests profile ID stored in session.
   */
  public function testStoredProfileIdInSession(): void {
    $session = $this->createMock(SessionInterface::class);
    $session->method('get')->with('autologout_alterable_profile_id')->willReturn('uuid-stored');
    $this->container->set('session', $session);

    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $this->assertEquals('uuid-stored', $profile->getId());
  }

  /**
   * Tests the getSessionExpiresIn method.
   */
  public function testGetSessionExpiresIn(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $expiresIn = $this->sessionExpiration->getTimestamp() - (new \DateTime())->getTimestamp();

    $this->assertEquals($expiresIn, $profile->getSessionExpiresIn());
  }

  /**
   * Tests the getLastActivityAgo method.
   */
  public function testGetLastActivityAgo(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $lastActivityAgo = (new \DateTime())->getTimestamp() - $this->lastActivity->getTimestamp();

    $this->assertEquals($lastActivityAgo, $profile->getLastActivityAgo());
  }

  /**
   * Tests the setLastActivity method.
   */
  public function testSetLastActivity(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $newActivity = new \DateTime('yesterday');
    $profile->setLastActivity($newActivity);

    $this->assertSame($newActivity, $profile->getLastActivity());
  }

  /**
   * Tests the setSessionExpiration method.
   */
  public function testSetSessionExpiration(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $newExpiration = new \DateTime('+2 hours');
    $profile->setSessionExpiration($newExpiration);

    $this->assertSame($newExpiration, $profile->getSessionExpiration());
  }

  /**
   * Tests the setExtendible method.
   */
  public function testSetExtendible(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $profile->setExtendible(FALSE);

    $this->assertFalse($profile->isExtendible());
  }

  /**
   * Tests the setRedirectUrl method.
   */
  public function testSetRedirectUrl(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $newUrl = $this->createMock(Url::class);
    $profile->setRedirectUrl($newUrl);

    $this->assertSame($newUrl, $profile->getRedirectUrl());
  }

  /**
   * Tests the toArray method.
   */
  public function testToArray(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $array = $profile->toArray();

    $this->assertArrayHasKey('id', $array);
    $this->assertArrayHasKey('lastActivity', $array);
    $this->assertArrayHasKey('lastActivityAgo', $array);
    $this->assertArrayHasKey('sessionExpiration', $array);
    $this->assertArrayHasKey('sessionExpiresIn', $array);
    $this->assertArrayHasKey('extendible', $array);
    $this->assertArrayHasKey('redirectUrl', $array);
  }

  /**
   * Tests the limited toArray method.
   */
  public function testLimitedToArray(): void {
    $profile = new AutologoutProfile($this->lastActivity, $this->sessionExpiration, $this->redirectUrl, TRUE);
    $array = $profile->toArray(TRUE);

    $this->assertArrayHasKey('id', $array);
    $this->assertArrayNotHasKey('lastActivity', $array);
    $this->assertArrayHasKey('lastActivityAgo', $array);
    $this->assertArrayNotHasKey('sessionExpiration', $array);
    $this->assertArrayHasKey('sessionExpiresIn', $array);
    $this->assertArrayHasKey('extendible', $array);
    $this->assertArrayHasKey('redirectUrl', $array);
  }

}
