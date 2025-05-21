# Autologout Alterable Module

## Introduction
The Autologout Alterable module is a module that allows you to configure session
timeout. It can be done per user level, per role level or globally. Via the
permission "Infinite session timeout" user can be excluded from the session
timeout, but can still be altered by other modules.

The session timeout is calculated from the user last activity. User last
activity is updated on each request and on (on drupal rendered paged)
interaction on the client side like clicks, keypresses etc. via the
autologout.js script.

This module is heavily inspired by the
[autologout module](https://www.drupal.org/project/autologout) module, but
optimized to be more alterable.

## Modal in drupal rendered pages
The module provides a modal that will be shown to the user when the session is
about to expire. The modal will be shown a configurable set of seconds before
the session expires, giving the user the option to extend the session.

## Configuration
The module can be configured on the configuration page at
`/admin/config/people/autologout_alterable`. Can be reached via
`Configuration -> People -> Automated logout settings`.

## Altering the behavior
The behavior of the module can be altered in many different ways.

### Altering via event subscribers
The module provides events that can be used to alter the behavior of the module.
See the event types available in the
`Drupal\autologout_alterable\Events\AutologoutEvents` class.

Further see the events classes in the same namespace to see what events are
available what data they provide to alter.

The following behavior can be altered via event subscribers.

#### Altering enabled state
The module provides an event that can be used to alter the enabled state of the
session calculation. It is called on every request. If altered to not be enabled
the activity will not be updated nor the session expiry time. This can be used
to disable the module for certain users or circumstances.

#### Altering set last activity
The module provides an event that can be used to alter the last activity time.
It is called on every request unless the session is already expired. Modules may
alter this to an different time (for example if an other subsystem has more
recent information about the user activity in a decoupled or single sign on
setup). It can also be altered to tell the autologout_alterable module to not
store this activity.

#### Altering the autologout profile
The module uses a profile that has information about last activity, session
expiry, after logout redirect url etc. See the
`Drupal\autologout_alterable\Utility\AutologoutProfileInterface` for more
information.

The module provides an event that can be used to alter the profile. It is called
on every request before determining if the session is expired and user should
be logged out.

### Altering via api
The module provides an api that can be used to get or update the autologout
profile. If page is rendered via drupal this api is used by the autologout.js
script so no need for other modules to implement this. But if a decoupled setup
is used this api can be used to get and update the profile and keep the user
logged in, assuming the session is shared between the decoupled site and the
drupal.

To get the profile do a GET request to
`/api/autologout_alterable/autologout-profile`. The profile is returned as a
json object with the following properties.

* id - The id of the profile
* lastActivityAgo - The time since last activity in seconds.
* sessionExpiresIn - The time until the session expires in seconds.
* extendible - If the session is extendible.
* redirectUrl - The url to redirect to after logout.

To update the profile do a PATCH request to
`/api/autologout_alterable/autologout-profile`. The last activity time should be
sent in the body as a json object with the property `lastActiveAgo` that should
be the time since last activity in seconds. Negative values (e.g. future times)
will be ignored.

To force a logout from client side via the api do a PATCH request above should
include the property `forceLogout` with the boolean value `true`.

The PATCH request will return the updated profile as a json object, where the
redirectUrl may be essential to use to redirect user to the expected path.
