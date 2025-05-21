<?php

namespace Drupal\autologout_alterable;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\autologout_alterable\Utility\AutologoutProfileInterface;

/**
 * Interface for AutologoutManager.
 */
interface AutologoutManagerInterface {

  /**
   * Check if the autologout feature globally is enabled.
   *
   * @return bool
   *   TRUE if the autologout feature is enabled, otherwise FALSE.
   */
  public function isEnabled(): bool;

  /**
   * Check if the current route is an autologout route.
   *
   * @return bool
   *   If the current route is an autologout route.
   */
  public function isAutologoutRoute(): bool;

  /**
   * Set the last activity time for current user.
   *
   * Last activity cannot be a future time.
   * If no last activity time is set, the current time is used.
   *
   * @param \DateTime|null $last_activity
   *   (Optional) The last activity time.
   *
   * @return \DateTime|null
   *   The actually set last activity time.
   */
  public function setLastActivity(?\DateTime $last_activity = NULL): ?\DateTime;

  /**
   * Get the autologout profile for the current user.
   *
   * @param array $redirect_extra_query
   *   (Optional) Extra query parameters to pass to the redirect URL.
   *
   * @return \Drupal\autologout_alterable\Utility\AutologoutProfileInterface
   *   The autologout profile for the current user.
   */
  public function getAutoLogoutProfile(array $redirect_extra_query = []): AutologoutProfileInterface;

  /**
   * Clear internal static cache of autologout profiles.
   *
   * @param int|null $uid
   *   (Optional) The user id to clear the profile for, or NULL to clear all.
   */
  public function clearAutoLogoutProfiles(?int $uid = NULL): void;

  /**
   * Get drupal settings for the autologout script.
   *
   * @return array
   *   The drupal settings for the autologout script.
   */
  public function getDrupalSettings(): array;

  /**
   * Make the user induced logout message.
   */
  public function makeInducedLogoutMessage(): bool;

  /**
   * Make the inactivity message.
   */
  public function makeInactivityMessage(): bool;

  /**
   * Logout the current user.
   *
   * @param bool $check_message
   *   (Optional) Whether to check for an inactivity message.
   * @param array $extra_query
   *   (Optional) Extra query parameters to pass to the redirect URL.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function logout(bool $check_message = TRUE, array $extra_query = []): TrustedRedirectResponse;

}
