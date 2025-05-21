<?php

namespace Drupal\autologout_alterable\Events;

/**
 * Autologout events.
 */
final class AutologoutEvents {

  /**
   * Event dispatched when resolving autologout enabled.
   *
   * Use subscriber to this event to alter if autologout_alterable is enabled,
   * for example depending on current user or route.
   */
  public const ALTER_ENABLED = 'autologout_alterable_alter_enabled';

  /**
   * Event dispatched when set last activity is called.
   *
   * Use subscriber to trigger callbacks for the event, alter the last activity
   * time, alter if last activity time should be stored etc.
   */
  public const SET_LAST_ACTIVITY = 'autologout_alterable_set_last_activity';

  /**
   * Event dispatched when autologout profile is created.
   *
   * Use subscriber to alter expiry time, redirect url etc.
   */
  public const AUTOLOGOUT_PROFILE_ALTER = 'autologout_alterable_autologout_profile_alter';

}
