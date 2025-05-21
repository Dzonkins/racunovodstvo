<?php

namespace Drupal\autologout_alterable\Events;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for autologout alter enabled.
 */
class AutologoutAlterEnabledEvent extends Event {

  /**
   * Constructs a new AutologoutAlterEnabledEvent object.
   *
   * @param bool $enabled
   *   If autologout is enabled.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $currentRequest
   *   The current request.
   */
  public function __construct(
    protected bool $enabled,
    protected AccountInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
    protected Request $currentRequest,
  ) {}

  /**
   * Check if autologout is enabled.
   *
   * @return bool
   *   If autologout is enabled.
   */
  public function isEnabled(): bool {
    return $this->enabled;
  }

  /**
   * Set the enabled status value.
   *
   * @param bool $value
   *   The enabled status value.
   *
   * @return $this
   *   Return self.
   */
  public function setEnabled(bool $value): self {
    $this->enabled = $value;
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

}
