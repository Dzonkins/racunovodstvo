<?php

namespace Drupal\autologout_alterable\Utility;

use Drupal\Core\Url;

/**
 * Interface for the autologout profile.
 */
interface AutologoutProfileInterface {

  public const EXPIRES_IN_NOT_APPLICABLE = PHP_INT_MAX;
  public const LAST_ACTIVITY_NOT_APPLICABLE = -1;

  /**
   * Gets the ID of the profile.
   *
   * @return string
   *   The ID of the profile.
   */
  public function getId(): string;

  /**
   * Gets the session expiration time in seconds.
   *
   * @return int
   *   The number of seconds until the session expires,
   *   or EXPIRES_IN_NOT_APPLICABLE if not applicable.
   */
  public function getSessionExpiresIn(): int;

  /**
   * Gets the time since the last activity in seconds.
   *
   * @return int
   *   The number of seconds since the last activity,
   *   or LAST_ACTIVITY_NOT_APPLICABLE if not applicable.
   */
  public function getLastActivityAgo(): int;

  /**
   * Gets the last activity time.
   *
   * @return \DateTime|null
   *   The last activity time or NULL if not applicable.
   */
  public function getLastActivity(): ?\DateTime;

  /**
   * Sets the last activity time.
   *
   * @param \DateTime|null $lastActivity
   *   The last activity time.
   *
   * @return self
   *   The current instance.
   */
  public function setLastActivity(?\DateTime $lastActivity): self;

  /**
   * Gets the session expiration time.
   *
   * @return \DateTime|null
   *   The session expiration time or NULL if not applicable.
   */
  public function getSessionExpiration(): ?\DateTime;

  /**
   * Sets the session expiration time.
   *
   * @param \DateTime|null $sessionExpiration
   *   The session expiration time.
   *
   * @return self
   *   The current instance.
   */
  public function setSessionExpiration(?\DateTime $sessionExpiration): self;

  /**
   * Checks if the session is extendible.
   *
   * @return bool
   *   TRUE if the session is extendible, FALSE otherwise.
   */
  public function isExtendible(): bool;

  /**
   * Set if the session is extendible.
   *
   * @param bool $value
   *   Whether the session is extendible.
   *
   * @return self
   *   The current instance.
   */
  public function setExtendible(bool $value): self;

  /**
   * Gets the redirect URL.
   *
   * @return \Drupal\Core\Url|null
   *   The redirect URL or NULL if not applicable.
   */
  public function getRedirectUrl(): ?Url;

  /**
   * Sets the redirect URL.
   *
   * @param \Drupal\Core\Url $redirectUrl
   *   The redirect URL.
   *
   * @return self
   *   The current instance.
   */
  public function setRedirectUrl(Url $redirectUrl): self;

  /**
   * Convert to array.
   *
   * @param bool $skip_date_objects
   *   Whether to skip date objects in the array.
   *
   * @return array
   *   Autologout profile as an array.
   */
  public function toArray(bool $skip_date_objects = FALSE): array;

}
