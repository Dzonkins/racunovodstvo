<?php

namespace Drupal\autologout_alterable\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\autologout_alterable\AutologoutManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for autologout_alterable module routes.
 */
class AutologoutController implements ContainerInjectionInterface {

  /**
   * Constructs a new AutologoutController object.
   *
   * @param \Drupal\autologout_alterable\AutologoutManagerInterface $autologoutManager
   *   The autologout manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected AutologoutManagerInterface $autologoutManager,
    protected RequestStack $requestStack,
    protected TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('autologout_alterable.manager'),
      $container->get('request_stack'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Make a profile response.
   *
   * NOTE: if session is expired a logout will be done for the current user.
   *
   * @param bool $force_logout
   *   Whether to force logout.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The profile response.
   */
  protected function makeProfileResponse(bool $force_logout = FALSE): JsonResponse {
    $profile = $this->autologoutManager->getAutoLogoutProfile();
    $session_expires_in = $profile->getSessionExpiresIn();
    $session_expires_in = min($session_expires_in, 9007199254740991);

    if ($force_logout) {
      $session_expires_in = 0;
    }

    $normalized = $profile->toArray(TRUE);
    $normalized['sessionExpiresIn'] = $session_expires_in;

    if ($session_expires_in <= 0) {
      $redirect_response = $this->autologoutManager->logout();
      $redirect_url = $redirect_response->getTargetUrl();
      $normalized['redirectUrl'] = $redirect_url;
    }

    $response = new JsonResponse($normalized, Response::HTTP_OK);
    $response->headers->addCacheControlDirective('no-cache');
    $response->headers->addCacheControlDirective('no-store');
    $response->headers->addCacheControlDirective('must-revalidate');
    $response->setMaxAge(0);
    return $response;
  }

  /**
   * Get the autologout profile.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The profile response.
   */
  public function getAutologoutProfile(): JsonResponse {
    return $this->makeProfileResponse();
  }

  /**
   * Update the autologout profile.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The profile response.
   */
  public function updateAutologoutProfile(): JsonResponse {
    $data = Json::decode($this->requestStack->getCurrentRequest()?->getContent() ?? '[]');

    $last_active_ago = is_array($data) && array_key_exists('lastActiveAgo', $data) ? $data['lastActiveAgo'] : NULL;
    if (is_numeric($last_active_ago) && $last_active_ago >= 0) {
      $last_active = new \DateTime('@' . ($this->time->getRequestTime() - $last_active_ago));
      $this->autologoutManager->setLastActivity($last_active);
    }

    $force_logout = is_array($data) && array_key_exists('forceLogout', $data) && $data['forceLogout'] === TRUE;
    return $this->makeProfileResponse($force_logout);
  }

}
