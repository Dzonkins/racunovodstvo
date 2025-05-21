<?php

namespace Drupal\autologout_alterable\Events;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\autologout_alterable\Utility\AutologoutProfileInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for autologout profile alteration.
 */
class AutologoutProfileAlterEvent extends Event {

  /**
   * Constructs a new AutologoutProfileAlterEvent object.
   *
   * @param \Drupal\autologout_alterable\Utility\AutologoutProfileInterface $autologoutProfile
   *   The autologout profile.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $currentRequest
   *   The current request.
   * @param \DateTime $requestTime
   *   The request time.
   */
  public function __construct(
    protected AutologoutProfileInterface $autologoutProfile,
    protected AccountInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
    protected Request $currentRequest,
    protected \DateTime $requestTime,
  ) {}

  /**
   * Get the autologout profile.
   *
   * @return \Drupal\autologout_alterable\Utility\AutologoutProfileInterface
   *   The autologout profile.
   */
  public function getAutologoutProfile(): AutologoutProfileInterface {
    return $this->autologoutProfile;
  }

  /**
   * Set the autologout profile.
   *
   * @param \Drupal\autologout_alterable\Utility\AutologoutProfileInterface $autologoutProfile
   *   The autologout profile.
   */
  public function setAutologoutProfile(AutologoutProfileInterface $autologoutProfile): self {
    $this->autologoutProfile = $autologoutProfile;
    return $this;
  }

  /**
   * Get the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  public function getCurrentUser(): AccountInterface {
    return $this->currentUser;
  }

  /**
   * Get the route match.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match.
   */
  public function getRouteMatch(): RouteMatchInterface {
    return $this->routeMatch;
  }

  /**
   * Get the current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The current request.
   */
  public function getCurrentRequest(): Request {
    return $this->currentRequest;
  }

  /**
   * Get the request time.
   *
   * @return \DateTime
   *   The request time.
   */
  public function getRequestTime(): \DateTime {
    return $this->requestTime;
  }

}
