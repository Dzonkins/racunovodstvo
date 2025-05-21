<?php

namespace Drupal\autologout_alterable\Events;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for autologout set last activity.
 */
class AutologoutSetLastActivityEvent extends Event {

  /**
   * Constructs a new AutologoutSetLatestActivityEvent object.
   *
   * @param \DateTime $lastActivity
   *   The last activity.
   * @param bool $lastActivityShouldBeStored
   *   If last activity should be stored.
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
    protected \DateTime $lastActivity,
    protected bool $lastActivityShouldBeStored,
    protected AccountInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
    protected Request $currentRequest,
    protected \DateTime $requestTime,
  ) {}

  /**
   * Get the last activity.
   *
   * @return \DateTime
   *   The last activity.
   */
  public function getLastActivity(): \DateTime {
    return $this->lastActivity;
  }

  /**
   * Set the last activity.
   *
   * @param \DateTime $latest_activity
   *   The last activity.
   *
   * @return $this
   *   Return self.
   */
  public function setLastActivity(\DateTime $latest_activity): self {
    $this->lastActivity = $latest_activity;
    return $this;
  }

  /**
   * Check if last activity should be stored.
   *
   * @return bool
   *   If last activity should be stored.
   */
  public function lastActivityShouldBeStored(): bool {
    return $this->lastActivityShouldBeStored;
  }

  /**
   * Set if last activity should be stored.
   *
   * @param bool $value
   *   If last activity should be stored.
   *
   * @return $this
   *   Return self.
   */
  public function setLastActivityShouldBeStored(bool $value): self {
    $this->lastActivityShouldBeStored = $value;
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
