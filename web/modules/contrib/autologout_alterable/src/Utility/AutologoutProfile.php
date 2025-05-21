<?php

namespace Drupal\autologout_alterable\Utility;

use Drupal\Core\Url;

/**
 * Object to describe the autologout profile.
 */
class AutologoutProfile implements AutologoutProfileInterface {

  /**
   * Constructs a new AutologoutProfile object.
   *
   * @param \DateTime|null $lastActivity
   *   The last activity time.
   * @param \DateTime|null $sessionExpiration
   *   The session expiration time.
   * @param \Drupal\Core\Url $redirectUrl
   *   The redirect URL (after expired session).
   * @param bool $extendible
   *   Whether the session is extendible.
   */
  public function __construct(
    protected ?\DateTime $lastActivity,
    protected ?\DateTime $sessionExpiration,
    protected Url $redirectUrl,
    protected bool $extendible = TRUE,
  ) {}

  /**
   * Gets the current request time.
   *
   * @return \DateTime
   *   The current request time as a \DateTime object.
   */
  protected function getCurrentRequestTime(): \DateTime {
    // @phpstan-ignore-next-line
    return new \DateTime('@' . \Drupal::time()->getRequestTime());
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session_service */
    // @phpstan-ignore-next-line
    $session_service = \Drupal::service('session');
    if ($session_service->get('autologout_alterable_profile_id')) {
      return $session_service->get('autologout_alterable_profile_id');
    }

    /** @var \Drupal\Component\Uuid\UuidInterface $uuid_service */
    // @phpstan-ignore-next-line
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();
    $session_service->set('autologout_alterable_profile_id', $uuid);
    return $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionExpiresIn(): int {
    if ($this->sessionExpiration === NULL) {
      return self::EXPIRES_IN_NOT_APPLICABLE;
    }
    return $this->sessionExpiration->getTimestamp() - $this->getCurrentRequestTime()->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastActivityAgo(): int {
    if ($this->lastActivity === NULL) {
      return self::LAST_ACTIVITY_NOT_APPLICABLE;
    }
    return $this->getCurrentRequestTime()->getTimestamp() - $this->lastActivity->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastActivity(): ?\DateTime {
    return $this->lastActivity;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastActivity(?\DateTime $lastActivity): self {
    $this->lastActivity = $lastActivity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionExpiration(): ?\DateTime {
    return $this->sessionExpiration;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionExpiration(?\DateTime $sessionExpiration): self {
    $this->sessionExpiration = $sessionExpiration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExtendible(): bool {
    return $this->extendible;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtendible(bool $value): self {
    $this->extendible = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(): ?Url {
    return $this->redirectUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectUrl(Url $redirectUrl): self {
    $this->redirectUrl = $redirectUrl;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(bool $skip_date_objects = FALSE): array {
    $last_activity_ago = $this->getLastActivityAgo();
    if ($last_activity_ago === self::LAST_ACTIVITY_NOT_APPLICABLE) {
      $last_activity_ago = NULL;
    }
    $session_expires_in = $this->getSessionExpiresIn();
    if ($session_expires_in === self::EXPIRES_IN_NOT_APPLICABLE) {
      $session_expires_in = NULL;
    }

    $redirect_url = $this->redirectUrl;
    $redirect_url->setOption('absolute', TRUE);

    $array = [
      'id' => $this->getId(),
      'lastActivity' => $this->lastActivity?->format('c'),
      'lastActivityAgo' => $last_activity_ago,
      'sessionExpiration' => $this->sessionExpiration?->format('c'),
      'sessionExpiresIn' => $session_expires_in,
      'extendible' => $this->extendible,
      'redirectUrl' => $redirect_url->toString(TRUE)->getGeneratedUrl(),
    ];

    if ($skip_date_objects) {
      unset($array['lastActivity'], $array['sessionExpiration']);
    }

    return $array;
  }

}
