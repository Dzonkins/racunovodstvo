/**
 * @file
 * JavaScript for autologout_alterable.
 */

/* eslint-disable no-var */
/* eslint-disable prettier/prettier */
/* eslint-disable prefer-template */
/* eslint-disable object-shorthand */
(function (Drupal, debounce, once, drupalSettings) {
  // Define constants.
  var AUTOLOGOUT_KEEP_ALIVE_TIMEOUT = 30000;
  var AUTOLOGOUT_PENDING_MAX = 120000;
  var AUTOLOGOUT_PENDING_MIN = 3000;
  var AUTOLOGOUT_DIALOG_LIMIT = 60000;
  var AUTOLOGOUT_SAFE_MARGIN = 5000;
  var AUTOLOGOUT_LOGOUT_REDIRECT_SAFE = 3000;
  var AUTOLOGOUT_BYPASS_DIALOG_LIMIT = 10000;

  // General dialog settings.
  var AUTOLOGOUT_SKIP_DIALOG = false;
  var AUTOLOGOUT_DIALOG_WIDTH = 450;
  var AUTOLOGOUT_COUNTDOWN_FORMAT = '%hours%:%mins%:%secs%';

  // Dialog settings extendible.
  var AUTOLOGOUT_DIALOG_TITLE = Drupal
    ? Drupal.t('You are about to be logged out')
    : 'You are about to be logged out';
  var AUTOLOGOUT_DIALOG_MESSAGE = Drupal
    ? Drupal.t(
        'We are about to log you out for inactivity. If we do, you will lose any unsaved work. Do you need more time?'
      )
    : 'We are about to log you out for inactivity. If we do, you will lose any unsaved work. Do you need more time?';
  var AUTOLOGOUT_DIALOG_STAY_BUTTON = Drupal ? Drupal.t('Yes') : 'Yes';
  var AUTOLOGOUT_DIALOG_LOGOUT_BUTTON = Drupal ? Drupal.t('No') : 'No';

  // Dialog settings not extendible.
  var AUTOLOGOUT_DIALOG_TITLE_NOT_EXTENDIBLE = Drupal
    ? Drupal.t('You are about to be logged out')
    : 'You are about to be logged out';
  var AUTOLOGOUT_DIALOG_MESSAGE_NOT_EXTENDIBLE = Drupal
    ? Drupal.t(
        'Your session is about to be expired and cannot be extended. Save any unsaved work now.'
      )
    : 'Your session is about to be expired and cannot be extended. Save any unsaved work now.';
  var AUTOLOGOUT_DIALOG_CLOSE_BUTTON_NOT_EXTENDIBLE = Drupal
    ? Drupal.t('Close message')
    : 'Close message';
  var AUTOLOGOUT_DIALOG_LOGOUT_BUTTON_NOT_EXTENDIBLE = Drupal
    ? Drupal.t('Logout now')
    : 'Logout now';

  var AFTER_LOGOUT_REDIRECT_URL = window.location.origin + '/';
  var AFTER_LOGOUT_REDIRECT_URL_DESTINATION = null;

  // Logged out dialog settings. Dialog to show if user is logged out and we
  // have failed to auto redirect user.
  var AUTOLOGOUT_LOGGED_OUT_DIALOG_TITLE = Drupal
    ? Drupal.t('You have been logged out')
    : 'You have been logged out';
  var AUTOLOGOUT_LOGGED_OUT_DIALOG_MESSAGE = Drupal
    ? Drupal.t('Please log in again or follow the link below.')
    : 'Please log in again or follow the link below.';

  // Define local variables for user activity tracking.
  var userActivity = null;
  var lastActivity = new Date();
  // Define local variables for the autologout profile and dialog handling.
  var profileId = null;
  var pendingTimeout = null;
  var localSessionExpires = null;
  var localExtendible = true;
  var hideDialog = false;
  var dialogAction = false;
  var state = 'pending';
  var dialog = null;
  var countDownInterval = null;
  var loggedOutDialog = null;

  // Define callback vars.
  var debouncedSetUserActive;
  var debouncedSetUserInactive;
  var activityEventCallback;

  /**
   * Resolves autologout settings from drupal settings.
   */
  async function resolveAutologoutSettings() {
    var autoLogoutSettings = drupalSettings
      ? drupalSettings.autologout_alterable
      : undefined;

    if (!autoLogoutSettings || typeof autoLogoutSettings !== 'object') {
      return;
    }

    if (
      autoLogoutSettings.dialogLimit &&
      typeof autoLogoutSettings.dialogLimit === 'number' &&
      autoLogoutSettings.dialogLimit > 0
    ) {
      AUTOLOGOUT_DIALOG_LIMIT = autoLogoutSettings.dialogLimit * 1000;
    }

    if (autoLogoutSettings.showDialog === false) {
      AUTOLOGOUT_DIALOG_LIMIT = -99999999;
      AUTOLOGOUT_SKIP_DIALOG = true;
    }

    if (
      autoLogoutSettings.dialogWidth &&
      typeof autoLogoutSettings.dialogWidth === 'number' &&
      autoLogoutSettings.dialogWidth > 0
    ) {
      AUTOLOGOUT_DIALOG_WIDTH = autoLogoutSettings.dialogWidth;
    }

    if (
      autoLogoutSettings.countdownFormat &&
      typeof autoLogoutSettings.countdownFormat === 'string'
    ) {
      AUTOLOGOUT_COUNTDOWN_FORMAT = autoLogoutSettings.countdownFormat;
    }

    if (
      autoLogoutSettings.dialogTitle &&
      typeof autoLogoutSettings.dialogTitle === 'string'
    ) {
      AUTOLOGOUT_DIALOG_TITLE = autoLogoutSettings.dialogTitle;
    }

    if (
      autoLogoutSettings.dialogMessage &&
      typeof autoLogoutSettings.dialogMessage === 'string'
    ) {
      AUTOLOGOUT_DIALOG_MESSAGE = autoLogoutSettings.dialogMessage;
    }

    if (
      autoLogoutSettings.dialogStayButton &&
      typeof autoLogoutSettings.dialogStayButton === 'string'
    ) {
      AUTOLOGOUT_DIALOG_STAY_BUTTON = autoLogoutSettings.dialogStayButton;
    }

    if (
      autoLogoutSettings.dialogLogoutButton &&
      typeof autoLogoutSettings.dialogLogoutButton === 'string'
    ) {
      AUTOLOGOUT_DIALOG_LOGOUT_BUTTON = autoLogoutSettings.dialogLogoutButton;
    }

    if (
      autoLogoutSettings.dialogTitleNotExtendible &&
      typeof autoLogoutSettings.dialogTitleNotExtendible === 'string'
    ) {
      AUTOLOGOUT_DIALOG_TITLE_NOT_EXTENDIBLE =
        autoLogoutSettings.dialogTitleNotExtendible;
    }

    if (
      autoLogoutSettings.dialogMessageNotExtendible &&
      typeof autoLogoutSettings.dialogMessageNotExtendible === 'string'
    ) {
      AUTOLOGOUT_DIALOG_MESSAGE_NOT_EXTENDIBLE =
        autoLogoutSettings.dialogMessageNotExtendible;
    }

    if (
      autoLogoutSettings.dialogCloseButtonNotExtendible &&
      typeof autoLogoutSettings.dialogCloseButtonNotExtendible === 'string'
    ) {
      AUTOLOGOUT_DIALOG_CLOSE_BUTTON_NOT_EXTENDIBLE =
        autoLogoutSettings.dialogCloseButtonNotExtendible;
    }

    if (
      autoLogoutSettings.dialogLogoutButtonNotExtendible &&
      typeof autoLogoutSettings.dialogLogoutButtonNotExtendible === 'string'
    ) {
      AUTOLOGOUT_DIALOG_LOGOUT_BUTTON_NOT_EXTENDIBLE =
        autoLogoutSettings.dialogLogoutButtonNotExtendible;
    }

    if (
      autoLogoutSettings.loggedOutDialogTitle &&
      typeof autoLogoutSettings.loggedOutDialogTitle === 'string'
    ) {
      AUTOLOGOUT_LOGGED_OUT_DIALOG_TITLE =
        autoLogoutSettings.loggedOutDialogTitle;
    }

    if (
      autoLogoutSettings.loggedOutDialogMessage &&
      typeof autoLogoutSettings.loggedOutDialogMessage === 'string'
    ) {
      AUTOLOGOUT_LOGGED_OUT_DIALOG_MESSAGE =
        autoLogoutSettings.loggedOutDialogMessage;
    }

    if (
      autoLogoutSettings.destination &&
      typeof autoLogoutSettings.destination === 'string'
    ) {
      AFTER_LOGOUT_REDIRECT_URL_DESTINATION = autoLogoutSettings.destination;
    }
  }

  /**
   * Helper method to calculate the time left before the session expires.
   *
   * @param {number|null} sessionExpires
   *   The session expires timestamp in milliseconds.
   * @param fallback
   *   The fallback value if the session expires is not set.
   *
   * @returns {number|any}
   *   The time left before the session expires or supplied fallback value.
   */
  function calculateExpiresIn(
    sessionExpires,
    fallback = AUTOLOGOUT_KEEP_ALIVE_TIMEOUT * 2
  ) {
    if (!sessionExpires) {
      return fallback;
    }

    return sessionExpires - new Date().getTime();
  }

  /**
   * Get the time since the last activity.
   *
   * Note: If the user is currently active, the time since the last activity
   * will be 0.
   *
   * @returns {number|null}
   *   The time since the last activity or null if no activity is recorded.
   */
  function lastActiveAgo() {
    if (userActivity === true) {
      return 0;
    }
    if (lastActivity === null) {
      return null;
    }

    return new Date().getTime() - lastActivity.getTime();
  }

  /**
   * Validate the autologout profile property.
   *
   * Note: The property is considered valid if it is null or undefined.
   *
   * @param {string} property
   *   The property to validate.
   * @param {object} autologoutProfile
   *   The autologout profile.
   *
   * @returns {boolean}
   *   Returns true if the property is valid, otherwise false.
   */
  function autologoutProfilePropertyIsValid(property, autologoutProfile) {
    var profileValue;

    if (typeof property !== 'string') {
      return false;
    }
    if (typeof autologoutProfile !== 'object') {
      return false;
    }

    profileValue = autologoutProfile[property];
    // Accept null or undefined values.
    if (profileValue === undefined || profileValue === null) {
      return true;
    }
    if (property === 'sessionExpires' && typeof profileValue !== 'number') {
      return false;
    }
    if (property === 'redirectUrl' && typeof profileValue !== 'string') {
      return false;
    }
    if (property === 'extendible' && typeof profileValue!== 'boolean') {
      return false;
    }
    if (property === 'userInducedLogout' && typeof profileValue !== 'boolean') {
      return false;
    }

    return true;
  }

  /**
   * Get the autologout profile from local storage.
   *
   * Helper method for the getAutologoutProfile method.
   *
   * @param {boolean} updateCompatible
   *   If true, the profile will be validated for update last activity.
   * @param {boolean} forceLogoutCompatible
   *   It true, the profile will be validated for force logout.
   *
   * @returns {any|null}
   *   Returns the autologout profile from local storage if applicable.
   */
  function getLocalStorageAutologoutProfile(
    updateCompatible = true,
    forceLogoutCompatible = false
  ) {
    var storedAutologoutProfileRaw;
    var storedAutologoutProfile;
    var storageExpires;
    var sessionExpiresIn;

    if (profileId === null) {
      return null;
    }

    storedAutologoutProfileRaw = localStorage.getItem('autologoutProfile');
    if (!storedAutologoutProfileRaw) {
      return null;
    }
    storedAutologoutProfile = JSON.parse(storedAutologoutProfileRaw);
    if (
      !storedAutologoutProfile ||
      typeof storedAutologoutProfile !== 'object'
    ) {
      return null;
    }

    if (storedAutologoutProfile.id !== profileId) {
      return null;
    }

    storageExpires = storedAutologoutProfile.storageExpires;
    if (!storageExpires || storageExpires <= new Date().getTime()) {
      return null;
    }

    // Validate properties.
    if (!autologoutProfilePropertyIsValid('sessionExpires', storedAutologoutProfile)) {
      return null;
    }
    if (!autologoutProfilePropertyIsValid('redirectUrl', storedAutologoutProfile)) {
      return null;
    }
    if (!autologoutProfilePropertyIsValid('extendible', storedAutologoutProfile)) {
      return null;
    }
    if (!autologoutProfilePropertyIsValid('userInducedLogout', storedAutologoutProfile)) {
      return null;
    }

    sessionExpiresIn = calculateExpiresIn(
      storedAutologoutProfile.sessionExpires,
      null
    );

    if (forceLogoutCompatible) {
      if (storedAutologoutProfile.userInducedLogout || sessionExpiresIn <= 0) {
        if (!storedAutologoutProfile.userInducedLogout && dialogAction) {
          storedAutologoutProfile.userInducedLogout = true;
        }
        return storedAutologoutProfile;
      }
      return null;
    }

    if (updateCompatible) {
      if (sessionExpiresIn === null) {
        return null;
      }
      if (!storedAutologoutProfile.extendible) {
        return storedAutologoutProfile;
      }
      if (
        sessionExpiresIn > 0 &&
        sessionExpiresIn < AUTOLOGOUT_PENDING_MAX + AUTOLOGOUT_SAFE_MARGIN
      ) {
        return null;
      }

      if (dialogAction) {
        return null;
      }
    }

    return storedAutologoutProfile;
  }

  /**
   * Get the autologout profile, fetches the profile from the server if needed.
   *
   * @param {boolean} updateLastActivity
   *   If true, user activity will be updated.
   * @param {boolean} forceLogout
   *   If true, user will be logged out.
   *
   * @returns {Promise<any>}
   *   The autologout profile.
   */
  async function getAutologoutProfile(
    updateLastActivity = false,
    forceLogout = false
  ) {
    var autologoutProfile;
    var method;
    var responseRaw;
    var error;
    var response;
    var sessionExpires;
    var isUserInducedLogout;
    var storageExpiresAfter;

    autologoutProfile = getLocalStorageAutologoutProfile(
      updateLastActivity,
      forceLogout
    );

    if (!autologoutProfile) {
      method = updateLastActivity || forceLogout ? 'PATCH' : 'GET';
      responseRaw = await fetch(
        '/api/autologout_alterable/autologout-profile?_format=json',
        {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body:
            method === 'PATCH'
              ? JSON.stringify({
                  lastActiveAgo: !forceLogout && lastActiveAgo() !== null
                    ? Math.floor(lastActiveAgo() / 1000)
                    : null,
                  forceLogout: forceLogout,
                })
              : undefined,
        }
      );

      if (!responseRaw.ok) {
        error = new Error('Failed response: ' + responseRaw.status);
        error.data = error.data || {};
        error.data.status = responseRaw.status;
        throw error;
      }

      // Validate response.
      response = await responseRaw.json();
      if (
        !response ||
        typeof response !== 'object' ||
        typeof response.lastActivityAgo !== 'number' ||
        typeof response.sessionExpiresIn !== 'number' ||
        typeof response.extendible !== 'boolean' ||
        typeof response.id !== 'string'
      ) {
        throw new Error('Invalid response');
      }
      if (
        response.redirectUrl !== null &&
        typeof response.redirectUrl !== 'string'
      ) {
        throw new Error('Invalid response');
      }

      sessionExpires = new Date();
      sessionExpires.setTime(
        sessionExpires.getTime() + response.sessionExpiresIn * 1000
      );

      isUserInducedLogout = forceLogout && dialogAction;
      storageExpiresAfter = isUserInducedLogout
        ? AUTOLOGOUT_DIALOG_LIMIT + AUTOLOGOUT_SAFE_MARGIN * 2
        : AUTOLOGOUT_PENDING_MIN;

      autologoutProfile = {
        storageExpires: new Date().getTime() + storageExpiresAfter - 1,
        sessionExpires: sessionExpires.getTime(),
        extendible: response.extendible,
        redirectUrl:
          typeof response.redirectUrl === 'string'
            ? response.redirectUrl
            : window.location.origin + '/user/login',
        userInducedLogout: isUserInducedLogout,
        id: response.id,
      };
      profileId = autologoutProfile.id;

      localStorage.setItem(
        'autologoutProfile',
        JSON.stringify(autologoutProfile)
      );
    }

    autologoutProfile.sessionExpires -= AUTOLOGOUT_SAFE_MARGIN;

    localSessionExpires = autologoutProfile.sessionExpires;
    localExtendible = autologoutProfile.extendible;

    if (autologoutProfile.redirectUrl) {
      AFTER_LOGOUT_REDIRECT_URL = autologoutProfile.redirectUrl;
    }

    return autologoutProfile;
  }

  /**
   * Helper method to get the after logout redirect url.
   *
   * Note: fallback to the current page if the redirect url is invalid.
   *
   * @param {boolean} setAutologoutInduced
   *   If true, the autologout induced query parameter will be set.
   *
   * @returns {string}
   *   The after logout redirect url.
   */
  function getAfterLogoutRedirectUrl(setAutologoutInduced = false) {
    var baseUrl = window.location.origin;
    var url;

    // Redirect to defined url.
    try {
      if (!AFTER_LOGOUT_REDIRECT_URL) {
        AFTER_LOGOUT_REDIRECT_URL = baseUrl + '/user/login';
      }

      // Validate redirect url.
      url = new URL(AFTER_LOGOUT_REDIRECT_URL);

      if (
        !url.searchParams.has('autologout_induced') &&
        !url.searchParams.has('autologout_inactive')
      ) {
        url.searchParams.set('autologout_inactive', '1');
      }

      if (setAutologoutInduced) {
        url.searchParams.set('autologout_induced', '1');
        url.searchParams.delete('autologout_inactive');
      }

      if (AFTER_LOGOUT_REDIRECT_URL_DESTINATION) {
        url.searchParams.set(
          'destination',
          AFTER_LOGOUT_REDIRECT_URL_DESTINATION
        );
      }

      return url.href;
    } catch (error) {
      // Reload current page if redirect fails.
      return window.location.href;
    }
  }

  /**
   * Helper method to calculate the next state based on the current state.
   *
   * @param {string} currentState
   *   The current state.
   * @param {any} autologoutProfile
   *   The autologout profile.
   * @param {boolean} useDialogBypassLimit
   *   If true, the dialog bypass limit will be used.
   *
   * @returns {string}
   *   The next state.
   */
  function calculateNextState(
    currentState,
    autologoutProfile,
    useDialogBypassLimit = true
  ) {
    var expiresIn;
    var logoutMargin;

    if (autologoutProfile.userInducedLogout) {
      AFTER_LOGOUT_REDIRECT_URL = getAfterLogoutRedirectUrl(true);
      return 'logged_out';
    }

    expiresIn = calculateExpiresIn(autologoutProfile.sessionExpires, null);

    if (expiresIn === null) {
      return 'pending';
    }

    logoutMargin = useDialogBypassLimit ? AUTOLOGOUT_BYPASS_DIALOG_LIMIT : 0;
    if (expiresIn < logoutMargin) {
      return 'logout';
    }

    if (expiresIn < AUTOLOGOUT_DIALOG_LIMIT + AUTOLOGOUT_SAFE_MARGIN) {
      return 'dialog';
    }

    return 'pending';
  }

  /**
   * Helper method to get the formatted countdown string.
   *
   * @returns {string}
   *   The formatted countdown string.
   */
  function getFormattedCountDown() {
    var expiresIn;
    var expiresInSeconds;
    var output;
    var days;
    var hours;
    var mins;

    expiresIn = calculateExpiresIn(localSessionExpires, null);
    if (
      expiresIn === null ||
      expiresIn < 0 ||
      expiresIn > AUTOLOGOUT_DIALOG_LIMIT * 2 ||
      !AUTOLOGOUT_COUNTDOWN_FORMAT
    ) {
      // Clear output.
      return '';
    }

    expiresInSeconds = Math.floor(expiresIn / 1000);
    output = AUTOLOGOUT_COUNTDOWN_FORMAT;
    if (expiresIn < 0) {
      expiresInSeconds = 0;
    }

    // Tokens available: %days%, %hours%, %mins%, and %secs%.
    if (output.includes('%days%')) {
      days = Math.floor(expiresInSeconds / 86400);
      output = output.replace('%days%', days);
      expiresInSeconds -= days * 86400;
    }

    if (output.includes('%hours%')) {
      hours = Math.floor(expiresInSeconds / 3600);
      output = output.replace('%hours%', hours.toString().padStart(2, '0'));
      expiresInSeconds -= hours * 3600;
    }

    if (output.includes('%mins%')) {
      mins = Math.floor(expiresInSeconds / 60);
      output = output.replace('%mins%', mins.toString().padStart(2, '0'));
      expiresInSeconds -= mins * 60;
    }

    if (output.includes('%secs%')) {
      output = output.replace(
        '%secs%',
        expiresInSeconds.toString().padStart(2, '0')
      );
    }
    return output;
  }

  /**
   * Helper method to update the countdown element.
   */
  function updateCountdownElement() {
    var countDownElement = document.getElementById('autologout-countdown');
    if (!countDownElement) {
      return;
    }
    countDownElement.textContent = getFormattedCountDown();
  }

  /**
   * Helper method to run the countdown interval.
   *
   * Note: The countdown element will be updated every second.
   */
  function runCountdown() {
    if (countDownInterval) {
      clearInterval(countDownInterval);
    }

    if (!AUTOLOGOUT_COUNTDOWN_FORMAT) {
      return;
    }

    countDownInterval = setInterval(function () {
      updateCountdownElement();
    }, 1000);
  }

  /**
   * Helper method to close the dialog.
   */
  function closeDialog() {
    var dialogElement;

    if (countDownInterval) {
      clearInterval(countDownInterval);
      countDownInterval = null;
    }

    if (!dialog) {
      return;
    }

    // Close dialog.
    dialog.close();
    // Remove the dom element.
    dialogElement = document.querySelector('.autologout_alterable-dialog');
    if (dialogElement) {
      dialogElement.remove();
    }
    dialog = null;
  }

  /**
   * Helper method to make sure the dialog exists and is up to date and visible.
   */
  function assertDialog() {
    var dialogContentElement;
    var currentDialogExtendibleValue;
    var expectedDialogExtendibleValue;
    var dialogContent;
    var dialogButtons;
    var logoutOnClose;

    if (hideDialog) {
      closeDialog();
      return;
    }

    if (dialog) {
      dialogContentElement = document.getElementById(
        'autologout-dialog-content'
      );
      if (dialogContentElement) {
        currentDialogExtendibleValue =
          dialogContentElement.getAttribute('data-extendible');
        expectedDialogExtendibleValue = localExtendible ? 'true' : 'false';
        if (currentDialogExtendibleValue !== expectedDialogExtendibleValue) {
          // Hide the current dialog and start over.
          closeDialog();
          assertDialog();
        }
      }
      return;
    }
    // Open dialog.
    dialogContent = document.createElement('div');
    dialogContent.setAttribute('id', 'autologout-dialog-content');
    dialogContent.setAttribute(
      'data-extendible',
      localExtendible ? 'true' : 'false'
    );
    dialogContent.innerHTML = '';
    if (localExtendible && AUTOLOGOUT_DIALOG_MESSAGE) {
      dialogContent.innerHTML += '<p>' + AUTOLOGOUT_DIALOG_MESSAGE + '</p>';
    }
    if (!localExtendible && AUTOLOGOUT_DIALOG_MESSAGE_NOT_EXTENDIBLE) {
      dialogContent.innerHTML += '<p>' + AUTOLOGOUT_DIALOG_MESSAGE + '</p>';
    }
    dialogContent.innerHTML +=
      '<p id="autologout-countdown">' + getFormattedCountDown() + '</p>';

    dialogAction = false;
    dialogButtons = [];

    if (localExtendible) {
      if (AUTOLOGOUT_DIALOG_STAY_BUTTON.length) {
        dialogButtons.push({
          text: AUTOLOGOUT_DIALOG_STAY_BUTTON,
          class: 'button--primary autologout-stay-button',
          click: function () {
            dialogAction = true;
            /* eslint-disable-next-line no-use-before-define */
            updateUserActivity(true);
            /* eslint-disable-next-line no-use-before-define */
            stateNavigator('checking');
          },
        });
      }

      if (AUTOLOGOUT_DIALOG_LOGOUT_BUTTON.length) {
        dialogButtons.push({
          text: AUTOLOGOUT_DIALOG_LOGOUT_BUTTON,
          class: 'button--secondary autologout-logout-button',
          click: function () {
            dialogAction = true;
            /* eslint-disable-next-line no-use-before-define */
            stateNavigator('logout');
          },
        });
      }
    }

    if (!localExtendible) {
      if (AUTOLOGOUT_DIALOG_CLOSE_BUTTON_NOT_EXTENDIBLE.length) {
        dialogButtons.push({
          text: AUTOLOGOUT_DIALOG_CLOSE_BUTTON_NOT_EXTENDIBLE,
          class: 'button--primary autologout-close-button',
          click: function () {
            dialogAction = true;
            hideDialog = true;
            closeDialog();
          },
        });
      }

      if (AUTOLOGOUT_DIALOG_LOGOUT_BUTTON_NOT_EXTENDIBLE.length) {
        dialogButtons.push({
          text: AUTOLOGOUT_DIALOG_LOGOUT_BUTTON_NOT_EXTENDIBLE,
          class: 'button--secondary autologout-logout-button',
          click: function () {
            dialogAction = true;
            /* eslint-disable-next-line no-use-before-define */
            stateNavigator('logout');
          },
        });
      }
    }

    logoutOnClose = localExtendible;

    // Create the dialog
    dialog = Drupal.dialog(dialogContent, {
      title: localExtendible
        ? AUTOLOGOUT_DIALOG_TITLE
        : AUTOLOGOUT_DIALOG_TITLE_NOT_EXTENDIBLE,
      dialogClass: 'autologout_alterable-dialog',
      buttons: dialogButtons,
      closeOnEscape: false,
      draggable: true,
      resizable: false,
      width: AUTOLOGOUT_DIALOG_WIDTH,
      modal: true,
      close: function () {
        if (state !== 'dialog' && state !== 'dialog_checking') {
          return;
        }

        if (logoutOnClose) {
          dialogAction = true;
          /* eslint-disable-next-line no-use-before-define */
          stateNavigator('logout');
          return;
        }
        hideDialog = true;
        closeDialog();
      },
    });
    dialog.showModal();
    runCountdown();
  }

  /**
   * Helper method to assert the logged out dialog.
   *
   * Note this dialog is only shown if the user is logged out and we have failed
   * to auto redirect the user. Auto redirect may not be supported by all
   * browsers, hence the dialog instead as a fallback.
   */
  function assertLoggedOutDialog() {
    var dialogContent;
    var redirectUrl;

    closeDialog();

    if (loggedOutDialog) {
      return;
    }

    // Open dialog.
    dialogContent = document.createElement('div');
    dialogContent.setAttribute('id', 'autologout-logged-out-dialog-content');
    dialogContent.innerHTML = '';

    dialogContent.innerHTML +=
      '<p>' + AUTOLOGOUT_LOGGED_OUT_DIALOG_MESSAGE + '</p>';

    redirectUrl = getAfterLogoutRedirectUrl();
    dialogContent.innerHTML +=
      '<a href="' + redirectUrl + '">' + redirectUrl + '</a>';

    loggedOutDialog = Drupal.dialog(dialogContent, {
      title: AUTOLOGOUT_LOGGED_OUT_DIALOG_TITLE,
      dialogClass: 'autologout-logged-out-dialog',
      closeOnEscape: true,
      draggable: true,
      resizable: false,
      width: AUTOLOGOUT_DIALOG_WIDTH,
      modal: true,
    });
    loggedOutDialog.showModal();
  }

  /**
   * Helper method to handle changes in the state.
   *
   * @param {string} newState
   *   The new state.
   */
  function handleStateChange(newState) {
    var dialogStates = ['dialog', 'dialog_checking'];

    if (dialogStates.includes(newState)) {
      assertDialog();
    } else {
      closeDialog();
    }

    // Some browser does not accept window.location to be programmatically set.
    // Open a dialog with a timeout to manually redirect, if needed.
    if (newState === 'logged_out') {
      setTimeout(function () {
        assertLoggedOutDialog();
      }, AUTOLOGOUT_LOGOUT_REDIRECT_SAFE);
    }
  }

  /**
   * Helper method to calculate the next state timeout.
   *
   * @returns {number}
   *   The next state timeout in milliseconds.
   */
  function calculateTimeout() {
    var timeLeft;
    var timeoutDelay;

    // Calculate next timeout.
    // Key points of interest:
    // - AUTOLOGOUT_DIALOG_LIMIT: The time before expiry when the dialog
    // should be shown.
    // - AUTOLOGOUT_SAFE_MARGIN: Time before expiry to do a last check.
    // - The time the localSessionExpires is reached.
    timeLeft = calculateExpiresIn(localSessionExpires);
    if (!timeLeft || timeLeft <= 0) {
      return 1;
    }

    timeoutDelay = timeLeft;
    if (timeLeft > AUTOLOGOUT_DIALOG_LIMIT) {
      timeoutDelay = timeLeft - AUTOLOGOUT_DIALOG_LIMIT;
    } else if (timeLeft > AUTOLOGOUT_SAFE_MARGIN) {
      timeoutDelay = timeLeft - AUTOLOGOUT_SAFE_MARGIN;
    }

    // Ensure the delay is at least the minimum delay, but if it close to
    // the AUTO_LOGOUT_PENDING_MAX value keep the highest of those.
    if (
      Math.abs(timeoutDelay - AUTOLOGOUT_PENDING_MAX) < AUTOLOGOUT_PENDING_MIN
    ) {
      timeoutDelay = Math.max(timeoutDelay, AUTOLOGOUT_PENDING_MAX);
    } else {
      timeoutDelay = Math.min(timeoutDelay, AUTOLOGOUT_PENDING_MAX);
    }

    return timeoutDelay;
  }

  /**
   * The state navigator.
   *
   * @param {string} newState
   *   The new state to navigate to.
   */
  async function stateNavigator(newState) {
    var nextState;
    var timeout;
    var autologoutProfile;
    var lastActive;
    var hasBeenActive;

    if (pendingTimeout) {
      clearTimeout(pendingTimeout);
    }
    if (state !== newState) {
      state = newState;
      handleStateChange(newState);
    }

    if (state === 'destroyed') {
      console.error('Autologout has been destroyed.');
      return;
    }

    nextState = 'pending';

    if (state === 'init') {
      await resolveAutologoutSettings();
      nextState = 'checking';
      stateNavigator(nextState);
      return;
    }

    if (state === 'pending') {
      dialogAction = false;
      nextState = 'checking';
    }

    if (state === 'checking') {
      lastActive = lastActiveAgo();
      hasBeenActive =
        typeof lastActive === 'number'
          ? lastActive < AUTOLOGOUT_KEEP_ALIVE_TIMEOUT
          : false;
      try {
        autologoutProfile = await getAutologoutProfile(hasBeenActive);
        nextState = calculateNextState(state, autologoutProfile);
      } catch (error) {
        console.error(error);

        nextState = 'destroyed';
        if (
          typeof error.data === 'object' &&
          error.data &&
          error.data.status === 403
        ) {
          nextState = 'logged_out';
        }
      }
      hideDialog = false;
      stateNavigator(nextState);
      return;
    }

    if (state === 'dialog') {
      nextState = 'dialog_checking';
    }

    if (state === 'dialog_checking') {
      try {
        autologoutProfile = await getAutologoutProfile(false);
        nextState = calculateNextState(state, autologoutProfile, false);
      } catch (error) {
        console.error(error);

        nextState = 'destroyed';

        if (
          typeof error.data === 'object' &&
          error.data &&
          error.data.status === 403
        ) {
          nextState = 'logged_out';
        }
      }
      stateNavigator(nextState);
      return;
    }

    if (state === 'logout') {
      nextState = 'logged_out';
      try {
        // Do logout.
        autologoutProfile = await getAutologoutProfile(false, true);
        AFTER_LOGOUT_REDIRECT_URL = getAfterLogoutRedirectUrl(
          autologoutProfile.userInducedLogout
        );
      } catch (error) {
        // Do nothing.
      }
      stateNavigator(nextState);
      return;
    }

    if (state === 'logged_out') {
      // If dialog is skipped, do not trigger the logout redirect, instead
      // destroy the state.
      if (AUTOLOGOUT_SKIP_DIALOG) {
        stateNavigator('destroyed');
        return;
      }

      // Navigate the user to the redirect url.
      window.location.href = getAfterLogoutRedirectUrl();
      return;
    }

    timeout = calculateTimeout();
    pendingTimeout = setTimeout(function () {
      stateNavigator(nextState);
    }, timeout);
  }

  /**
   * Helper method to handle changes in user activity.
   *
   * @param {boolean} newActivity
   *   The new activity status.
   * @param {boolean} oldActivity
   *   The old activity status.
   */
  function handleActivityChange(newActivity, oldActivity) {
    var expiresIn;

    // If user has just become active in dialog state, do a dialog_checking to
    // verify that it should still be shown.
    if (newActivity && !oldActivity && state === 'dialog') {
      expiresIn = calculateExpiresIn(localSessionExpires, 0);
      if (expiresIn < AUTOLOGOUT_DIALOG_LIMIT - AUTOLOGOUT_SAFE_MARGIN) {
        stateNavigator('dialog_checking');
      }
    }
  }

  /**
   * Updates the user activity status and set the last activity time.
   *
   * @param {boolean} status
   *   The new status of the user activity.
   */
  function updateUserActivity(status) {
    if (userActivity !== status) {
      userActivity = status;
      lastActivity = new Date();
      handleActivityChange(status, !status);
    }

    if (status === true) {
      debouncedSetUserInactive();
    }
  }

  // Setups the debounce functions for user activity.
  debouncedSetUserActive = debounce(function () {
    updateUserActivity(true);
  }, 300);
  debouncedSetUserInactive = debounce(function () {
    updateUserActivity(false);
  }, AUTOLOGOUT_KEEP_ALIVE_TIMEOUT);

  /**
   * Callback for user activity events.
   */
  activityEventCallback = function () {
    if (!userActivity) {
      updateUserActivity(true);
      return;
    }
    debouncedSetUserActive();
  };

  /**
   * Attaches the behavior for autologoutAlterable.
   */
  Drupal.behaviors.autologoutAlterable = {
    attach: function (context, settings) {
      var body;
      var ignoreUserActivity = false;
      var useMouseActivity = true;
      var useTouchActivity = true;
      var useClickActivity = true;
      var useKeyActivity = true;
      var useScrollActivity = true;
      var autologoutAlterableSettings = {};

      if (!once('autologout-alterable-attached', 'html').length) {
        return;
      }

      autologoutAlterableSettings = settings && settings.autologout_alterable
        ? settings.autologout_alterable
        : {};

      if (typeof autologoutAlterableSettings === 'boolean') {
        ignoreUserActivity = settings.autologout_alterable.ignoreUserActivity;
      }
      if (typeof autologoutAlterableSettings.useMouseActivity === 'boolean') {
        useMouseActivity = settings.autologout_alterable.useMouseActivity;
      }
      if (typeof autologoutAlterableSettings.useTouchActivity === 'boolean') {
        useTouchActivity = settings.autologout_alterable.useTouchActivity;
      }
      if (typeof autologoutAlterableSettings.useClickActivity === 'boolean') {
        useClickActivity = settings.autologout_alterable.useClickActivity;
      }
      if (typeof autologoutAlterableSettings.useKeyActivity === 'boolean') {
        useKeyActivity = settings.autologout_alterable.useKeyActivity;
      }
      if (typeof autologoutAlterableSettings.useScrollActivity === 'boolean') {
        useScrollActivity = settings.autologout_alterable.useScrollActivity;
      }


      if (ignoreUserActivity) {
        updateUserActivity(false);
      } else {
        // Setup event listeners for user activity.
        body = document.querySelector('body');
        if (body) {
          if (useMouseActivity) {
            body.addEventListener('mousemove', activityEventCallback);
          }
          if (useTouchActivity) {
            body.addEventListener('touchmove', activityEventCallback);
          }
          if (useClickActivity) {
            body.addEventListener('click', activityEventCallback);
          }
          if (useKeyActivity) {
            body.addEventListener('keydown', activityEventCallback);
          }
        }
        if (useScrollActivity) {
          window.addEventListener('scroll', activityEventCallback);
        }
      }

      stateNavigator('init');
    },
  };
})(Drupal, Drupal.debounce, once, drupalSettings);
