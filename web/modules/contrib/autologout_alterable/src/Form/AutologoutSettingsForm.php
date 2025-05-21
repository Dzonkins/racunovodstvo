<?php

namespace Drupal\autologout_alterable\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for autologout module.
 */
class AutologoutSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The userData service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AutologoutSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManager $config_typed
   *   The typed config object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module manager service.
   * @param \Drupal\user\UserData $user_data
   *   The userData service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManager $config_typed, ModuleHandlerInterface $module_handler, UserData $user_data, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $config_typed);
    $this->moduleHandler = $module_handler;
    $this->userData = $user_data;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('user.data'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['autologout_alterable.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autologout_alterable_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autologout_alterable.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autologout'),
      '#default_value' => $config->get('enabled'),
      '#weight' => -20,
      '#description' => $this->t("Enable autologout on this site."),
    ];

    $form['session_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Session timeout value in seconds'),
      '#default_value' => $config->get('session_timeout'),
      '#size' => 8,
      '#description' => $this->t('The length of inactivity time, in seconds, before automated log out. Must be 60 seconds or greater. Will not be used if role timeout is activated.'),
    ];

    $form['max_session_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max session timeout in seconds'),
      '#default_value' => $config->get('max_session_timeout'),
      '#size' => 10,
      '#maxlength' => 12,
      '#description' => $this->t('The maximum logout threshold time that can be set by users who have the permission to set user level timeouts.'),
    ];

    $form['ignore_user_activity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore user activity'),
      '#default_value' => $config->get('ignore_user_activity'),
      '#description' => $this->t('Enable this to autologout user regardless of their activity.'),
    ];

    $form['use_individual_logout_threshold'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable user-specific logout thresholds'),
      '#default_value' => $config->get('use_individual_logout_threshold'),
      '#description' => $this->t("Enable this to allow autologout thresholds to be set users."),
    ];

    $form['use_infinite_session_for_privileged'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use infinite session for privileged users'),
      '#default_value' => $config->get('use_infinite_session_for_privileged'),
      '#description' => $this->t('Enable this to allow users with permission "Infinite session timeout" to have an infinite session. Sessions can still be altered by other modules.'),
    ];

    $form['include_destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#default_value' => $config->get('include_destination'),
      '#description' => $this->t('Enable this if you want the default redirect url (/user/login) to include destination to the current path.'),
    ];

    $form['client_activity_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Client activity settings'),
      '#open' => FALSE,
    ];

    $client_activity_options = [
      'mousemove',
      'touchmove',
      'click',
      'keydown',
      'scroll',
    ];

    foreach ($client_activity_options as $option) {
      $config_key = 'client_activity_' . $option;
      $form['client_activity_wrapper'][$config_key] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Client activity: @option', ['@option' => $option]),
        '#default_value' => $config->get($config_key),
        '#description' => $this->t('Consider @option as a client activity, that will affect the last activity state.', ['@option' => $option]),
      ];
    }

    $form['show_dialog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the logout dialog'),
      '#default_value' => $config->get('show_dialog'),
      '#description' => $this->t('Enable this if you want users to be shown a logout dialog before auto logout.'),
    ];

    $form['dialog_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog limit'),
      '#default_value' => $config->get('dialog_limit'),
      '#size' => 8,
      '#description' => $this->t('How many seconds to give a user to respond to the logout dialog before ending their session.'),
    ];

    $form['dialog_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog width'),
      '#default_value' => $config->get('dialog_width'),
      '#size' => 40,
      '#description' => $this->t('This modal dialog width in pixels.'),
    ];

    $form['countdown_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Autologout block time format'),
      '#default_value' => $config->get('countdown_format'),
      '#description' => $this->t('Change the display of the dynamic timer. Available replacement values are: %days%, %hours%, %mins%, and %secs%.'),
    ];

    $form['dialog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title to display in the logout dialog'),
      '#default_value' => $config->get('dialog_title'),
      '#size' => 40,
      '#description' => $this->t('This message must be plain text as it might appear in a JavaScript confirm dialog.'),
    ];

    $form['dialog_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to display in the logout dialog'),
      '#default_value' => $config->get('dialog_message'),
      '#size' => 40,
      '#description' => $this->t('This message must be plain text as it might appear in a JavaScript confirm dialog.'),
    ];

    $form['dialog_stay_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom keep logged in button text'),
      '#default_value' => $config->get('dialog_stay_button'),
      '#size' => 40,
      '#description' => $this->t('Add custom text to keep logged in button. Set to empty string to disable button.'),
    ];

    $form['dialog_logout_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom logout button text'),
      '#default_value' => $config->get('dialog_logout_button'),
      '#size' => 40,
      '#description' => $this->t('Add custom text to logout button. Set to empty string to disable button.'),
    ];

    $form['dialog_title_not_extendible'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title when session cannot be extended'),
      '#default_value' => $config->get('dialog_title_not_extendible'),
      '#size' => 40,
      '#description' => $this->t('This message must be plain text as it might appear in a JavaScript confirm dialog.'),
    ];

    $form['dialog_message_not_extendible'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to display when session cannot be extended'),
      '#default_value' => $config->get('dialog_message_not_extendible'),
      '#size' => 40,
      '#description' => $this->t('This message must be plain text as it might appear in a JavaScript confirm dialog.'),
    ];

    $form['dialog_close_button_not_extendible'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close message button text when session cannot be extended'),
      '#default_value' => $config->get('dialog_close_button_not_extendible'),
      '#size' => 40,
      '#description' => $this->t('Add custom text to the close message button. Set to empty string to disable button.'),
    ];

    $form['dialog_logout_button_not_extendible'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logout button text when session cannot be extended'),
      '#default_value' => $config->get('dialog_logout_button_not_extendible'),
      '#size' => 40,
      '#description' => $this->t('Add custom text to the logout message button. Set to empty string to disable button.'),
    ];

    $form['logged_out_dialog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title to display in the logged out dialog'),
      '#default_value' => $config->get('logged_out_dialog_title'),
      '#size' => 40,
      '#description' => $this->t('Dialog title after logged out if browser failed to redirect internally'),
    ];

    $form['logged_out_dialog_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to display in the logout dialog'),
      '#default_value' => $config->get('logged_out_dialog_message'),
      '#size' => 40,
      '#description' => $this->t('Dialog message to display after logged out if browser failed to redirect internally'),
    ];

    $form['inactivity_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to display to the user after they are logged out due to inactivity'),
      '#default_value' => $config->get('inactivity_message'),
      '#size' => 40,
      '#description' => $this->t('This message is displayed after the user was logged out due to inactivity. You can leave this blank to show no message to the user.'),
    ];

    $form['inactivity_message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of the inactivity message to display'),
      '#default_value' => $config->get('inactivity_message_type'),
      '#description' => $this->t('Specifies whether to display the message as status or warning.'),
      '#options' => [
        MessengerInterface::TYPE_STATUS => $this->t('Status'),
        MessengerInterface::TYPE_WARNING => $this->t('Warning'),
      ],
    ];

    $form['induced_logout_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to display to the user after they are logged out due to induced logout'),
      '#default_value' => $config->get('induced_logout_message'),
      '#size' => 40,
      '#description' => $this->t('This message is displayed after the user was logged out due to induced logout. You can leave this blank to show no message to the user.'),
    ];

    $form['induced_logout_message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of the induced logout message to display'),
      '#default_value' => $config->get('induced_logout_message_type'),
      '#description' => $this->t('Specifies whether to display the message as status or warning.'),
      '#options' => [
        MessengerInterface::TYPE_STATUS => $this->t('Status'),
        MessengerInterface::TYPE_WARNING => $this->t('Warning'),
      ],
    ];

    $form['use_watchdog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable watchdog Automated Logout logging'),
      '#default_value' => $config->get('use_watchdog'),
      '#description' => $this->t('Enable logging of automatically logged out users'),
    ];

    $form['whitelisted_ip_addresses'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Whitelisted ip addresses'),
      '#default_value' => $config->get('whitelisted_ip_addresses'),
      '#size' => 40,
      '#description' => $this->t('Users from these IP addresses will not be logged out.'),
    ];

    $form['role_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Role Timeout'),
      '#default_value' => $config->get('role_logout'),
      '#description' => $this->t('Enable each role to have its own timeout threshold and redirect URL, a refresh may be required for changes to take effect. Any role not ticked will use the default timeout value and default redirect URL. Any role can have a timeout value of 0 which means that they will never be logged out. Roles without specified redirect URL will use the default redirect URL.'),
    ];

    $form['role_logout_max'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use highest role timeout value'),
      '#default_value' => $config->get('role_logout_max'),
      '#description' => $this->t('Check this to use the highest timeout value instead of the lowest for users that have more than one role.'),
      '#states' => [
        'visible' => [
          // Only show this field when the 'role_logout' checkbox is enabled.
          ':input[name="role_logout"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['role_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          // Only show this field when the 'role_logout' checkbox is enabled.
          ':input[name="role_logout"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['role_container']['table'] = [
      '#type' => 'table',
      '#header' => [
        'enable' => $this->t('Customize'),
        'name' => $this->t('Role Name'),
        'session_timeout' => $this->t('Session timeout (seconds)'),
      ],
    ];

    foreach ($this->entityTypeManager->getStorage('user_role')->loadMultiple() as $key => $role) {
      if ($key === 'authenticated') {
        continue;
      }
      if ($key === 'anonymous') {
        continue;
      }

      $form['role_container']['table'][$key] = [
        'enabled' => [
          '#type' => 'checkbox',
          '#default_value' => $this->config('autologout_alterable.role.' . $key)->get('enabled'),
        ],
        'role' => [
          '#type' => 'item',
          '#value' => $key,
          '#markup' => $role->label(),
        ],
        'session_timeout' => [
          '#type' => 'textfield',
          '#default_value' => $this->config('autologout_alterable.role.' . $key)->get('session_timeout'),
          '#size' => 8,
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate timeout range.
   *
   * Checks to see if timeout threshold is outside max/min values. Done here
   * to centralize and stop repeated code. Hard coded min, configurable max.
   *
   * @param int $timeout
   *   The timeout value in seconds to validate.
   * @param int $max_session_timeout
   *   (optional) Maximum value of timeout. If not set, system default is used.
   *
   * @return bool
   *   Return TRUE or FALSE
   */
  public function timeoutValidate($timeout, $max_session_timeout = NULL) {
    if (is_null($max_session_timeout)) {
      $max_session_timeout = $this->config('autologout_alterable.settings')->get('max_session_timeout');
    }

    if (!is_numeric($timeout) || $timeout < 0 || ($timeout > 0 && $timeout < 60) || $timeout > $max_session_timeout) {
      // Less than 60, greater than max_session_timeout and is numeric.
      // 0 is allowed now as this means no timeout.
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $new_stack = [];
    if (!empty($values['table'])) {
      foreach ($values['table'] as $key => $pair) {
        if (is_array($pair)) {
          foreach ($pair as $pair_key => $pair_value) {
            $new_stack[$key][$pair_key] = $pair_value;
          }
        }
      }
    }

    $max_session_timeout = $values['max_session_timeout'];

    if ($values['role_logout']) {
      // Validate timeouts for each role.
      foreach (array_keys($this->entityTypeManager->getStorage('user_role')->loadMultiple()) as $role) {
        if (empty($new_stack[$role]) || empty($new_stack[$role]['enabled'])) {
          // Don't validate role timeouts for non enabled roles.
          continue;
        }

        $session_timeout = $new_stack[$role]['session_timeout'];
        $valid = $this->timeoutValidate($session_timeout, $max_session_timeout);
        if (!$valid) {
          $form_state->setErrorByName('table][' . $role . '][session_timeout', $this->t('%role role session_timeout must be an integer greater than 60, less then %max or 0 to disable autologout for that role.', [
            '%role' => $role,
            '%max' => $max_session_timeout,
          ]));
        }
      }
    }

    $session_timeout = $values['session_timeout'];
    // Validate session_timeout.
    if ($session_timeout < 60) {
      $form_state->setErrorByName('session_timeout', $this->t('The session_timeout value must be an integer 60 seconds or greater.'));
    }
    elseif ($max_session_timeout <= 60) {
      $form_state->setErrorByName('max_session_timeout', $this->t('The max session_timeout must be an integer greater than 60.'));
    }
    elseif (!is_numeric($session_timeout) || ((int) $session_timeout != $session_timeout) || $session_timeout < 60 || $session_timeout > $max_session_timeout) {
      $form_state->setErrorByName('session_timeout', $this->t('The session_timeout must be an integer greater than or equal to 60 and less then or equal to %max.', ['%max' => $max_session_timeout]));
    }

    // Validate ip address list.
    $whitelisted_ip_addresses_list = explode("\n", trim($values['whitelisted_ip_addresses']));

    foreach ($whitelisted_ip_addresses_list as $ip_address) {
      if (!empty($ip_address) && !filter_var(trim($ip_address), FILTER_VALIDATE_IP)) {
        $form_state->setErrorByName(
          'whitelisted_ip_addresses',
          $this->t('Whitelisted IP address list should contain only valid IP addresses, one per row')
        );
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $autologout_settings = $this->configFactory()->getEditable('autologout_alterable.settings');

    $old_use_individual_logout_threshold = $autologout_settings->get('use_individual_logout_threshold');
    $new_use_individual_logout_threshold = (bool) $values['use_individual_logout_threshold'];

    $autologout_settings
      ->set('enabled', $values['enabled'])
      ->set('session_timeout', $values['session_timeout'])
      ->set('max_session_timeout', $values['max_session_timeout'])
      ->set('ignore_user_activity', $values['ignore_user_activity'])
      ->set('use_individual_logout_threshold', $values['use_individual_logout_threshold'])
      ->set('use_infinite_session_for_privileged', $values['use_infinite_session_for_privileged'])
      ->set('role_logout', $values['role_logout'])
      ->set('role_logout_max', $values['role_logout_max'])
      ->set('include_destination', $values['include_destination'])
      ->set('client_activity_mousemove', $values['client_activity_mousemove'])
      ->set('client_activity_touchmove', $values['client_activity_touchmove'])
      ->set('client_activity_click', $values['client_activity_click'])
      ->set('client_activity_keydown', $values['client_activity_keydown'])
      ->set('client_activity_scroll', $values['client_activity_scroll'])
      ->set('show_dialog', $values['show_dialog'])
      ->set('dialog_limit', $values['dialog_limit'])
      ->set('dialog_width', $values['dialog_width'])
      ->set('countdown_format', $values['countdown_format'])
      ->set('dialog_title', $values['dialog_title'])
      ->set('dialog_message', $values['dialog_message'])
      ->set('dialog_stay_button', $values['dialog_stay_button'])
      ->set('dialog_logout_button', $values['dialog_logout_button'])
      ->set('dialog_title_not_extendible', $values['dialog_title_not_extendible'])
      ->set('dialog_message_not_extendible', $values['dialog_message_not_extendible'])
      ->set('dialog_close_button_not_extendible', $values['dialog_close_button_not_extendible'])
      ->set('dialog_logout_button_not_extendible', $values['dialog_logout_button_not_extendible'])
      ->set('inactivity_message', $values['inactivity_message'])
      ->set('inactivity_message_type', $values['inactivity_message_type'])
      ->set('induced_logout_message', $values['induced_logout_message'])
      ->set('induced_logout_message_type', $values['induced_logout_message_type'])
      ->set('logged_out_dialog_title', $values['logged_out_dialog_title'])
      ->set('logged_out_dialog_message', $values['logged_out_dialog_message'])
      ->set('dialog_message', $values['dialog_message'])
      ->set('use_watchdog', $values['use_watchdog'])
      ->set('whitelisted_ip_addresses', $values['whitelisted_ip_addresses'])
      ->save();

    if (!empty($values['table'])) {
      foreach ($values['table'] as $user) {
        $this->configFactory()->getEditable('autologout_alterable.role.' . $user['role'])
          ->set('enabled', $user['enabled'])
          ->set('session_timeout', $user['session_timeout'])
          ->save();
      }
    }

    // If individual logout threshold setting is no longer enabled,
    // clear existing individual timeouts from users.
    if ($old_use_individual_logout_threshold === TRUE && $new_use_individual_logout_threshold === FALSE) {
      $users_timeout = $this->userData->get('autologout_alterable', NULL, 'session_timeout');
      foreach ($users_timeout as $uid => $current_timeout_value) {
        if ($current_timeout_value !== NULL) {
          $this->userData->set('autologout_alterable', $uid, 'timeout', NULL);
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}
