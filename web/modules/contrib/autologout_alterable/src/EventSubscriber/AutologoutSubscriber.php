<?php

namespace Drupal\autologout_alterable\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\autologout_alterable\AutologoutManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for autologout.
 *
 * Handles immediate logout or update of last activity.
 */
class AutologoutSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new AutologoutSubscriber object.
   *
   * @param \Drupal\autologout_alterable\AutologoutManagerInterface $autologoutManager
   *   The autologout manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected AutologoutManagerInterface $autologoutManager,
    protected AccountInterface $currentUser,
    protected RequestStack $requestStack,
  ) {}

  /**
   * Handle request event.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    if ($this->currentUser->isAnonymous() && $this->requestStack->getCurrentRequest()->getMethod() === 'GET') {
      $query = $this->requestStack->getCurrentRequest()?->query ?? new InputBag();

      $anonymous_message_shown = FALSE;
      $show_induced_logout_message = $query->has('autologout_induced') && (int) $query->get('autologout_induced') === 1;
      if ($show_induced_logout_message) {
        $anonymous_message_shown = $this->autologoutManager->makeInducedLogoutMessage();
      }

      $show_inactive_message = !$anonymous_message_shown && $query->has('autologout_inactive') && (int) $query->get('autologout_inactive') === 1;
      if ($show_inactive_message) {
        $anonymous_message_shown = $this->autologoutManager->makeInactivityMessage();
      }
    }

    // Skip if autologout is disabled.
    if (!$this->autologoutManager->isEnabled()) {
      return;
    }

    // Autologout routes handles its own activity and remaining time. Skip
    // them in this subscriber.
    if ($this->autologoutManager->isAutologoutRoute()) {
      return;
    }

    if ($this->autologoutManager->getAutoLogoutProfile()->getSessionExpiresIn() <= 0) {
      $response = $this->autologoutManager->logout();
      $event->setResponse($response);
      return;
    }

    $this->autologoutManager->setLastActivity();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest', 20];
    return $events;
  }

}
