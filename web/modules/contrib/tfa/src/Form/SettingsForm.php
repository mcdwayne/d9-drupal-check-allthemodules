<?php

namespace Drupal\tfa\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\tfa\TfaDataTrait;
use Drupal\tfa\TfaLoginPluginManager;
use Drupal\tfa\TfaSendPluginManager;
use Drupal\tfa\TfaSetupPluginManager;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The admin configuration page.
 */
class SettingsForm extends ConfigFormBase {
  use TfaDataTrait;
  use StringTranslationTrait;

  /**
   * The login plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaLoginPluginManager
   */
  protected $tfaLogin;

  /**
   * The send plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaSendPluginManager
   */
  protected $tfaSend;

  /**
   * The validation plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $tfaValidation;

  /**
   * The setup plugin manager to fetch plugin information.
   *
   * @var \Drupal\tfa\TfaSetupPluginManager
   */
  protected $tfaSetup;

  /**
   * Provides the user data service object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Encryption profile manager to fetch the existing encryption profiles.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryptionProfileManager;

  /**
   * The admin configuraiton form constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory object.
   * @param \Drupal\tfa\TfaLoginPluginManager $tfa_login
   *   The login plugin manager.
   * @param \Drupal\tfa\TfaSendPluginManager $tfa_send
   *   The send plugin manager.
   * @param \Drupal\tfa\TfaValidationPluginManager $tfa_validation
   *   The validation plugin manager.
   * @param \Drupal\tfa\TfaSetupPluginManager $tfa_setup
   *   The setup plugin manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   Encrypt profile manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TfaLoginPluginManager $tfa_login, TfaSendPluginManager $tfa_send, TfaValidationPluginManager $tfa_validation, TfaSetupPluginManager $tfa_setup, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager) {
    parent::__construct($config_factory);
    $this->tfaLogin = $tfa_login;
    $this->tfaSend = $tfa_send;
    $this->tfaSetup = $tfa_setup;
    $this->tfaValidation = $tfa_validation;
    $this->encryptionProfileManager = $encryption_profile_manager;
    // User Data service to store user-based data in key value pairs.
    $this->userData = $user_data;
  }

  /**
   * Creates service objects for the class contructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to get the required services.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.tfa.login'),
      $container->get('plugin.manager.tfa.send'),
      $container->get('plugin.manager.tfa.validation'),
      $container->get('plugin.manager.tfa.setup'),
      $container->get('user.data'),
      $container->get('encrypt.encryption_profile.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfa_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = $this->currentUser()->id();
    $config = $this->config('tfa.settings');
    $form = [];

    // Get Login Plugins.
    $login_plugins = $this->tfaLogin->getDefinitions();

    // Get Send Plugins.
    $send_plugins = $this->tfaSend->getDefinitions();

    // Get Setup Plugins.
    $setup_plugins = $this->tfaSetup->getDefinitions();

    // Get Validation Plugins.
    $validation_plugins = $this->tfaValidation->getDefinitions();
    // Get validation plugin labels and their fallbacks.
    $validation_plugins_labels = [];
    $validation_plugins_fallbacks = [];
    $fallback_plugins_labels = [];
    foreach ($validation_plugins as $key => $plugin) {
      // Skip this plugin if no setup class is available.
      if (!isset($setup_plugins[$key . '_setup'])){
        unset($validation_plugins[$key]);
        continue;
      }
      if ($plugin['isFallback']) {
        $fallback_plugins_labels[$plugin['id']] = $plugin['label']->render();
        continue;
      }
      $validation_plugins_labels[$plugin['id']] = $plugin['label']->render();
      if (!empty($plugin['fallbacks'])) {
        $validation_plugins_fallbacks[$plugin['id']] = $plugin['fallbacks'];
      }
    }
    // Fetching all available encrpytion profiles.
    $encryption_profiles = $this->encryptionProfileManager->getAllEncryptionProfiles();

    $plugins_empty = $this->dataEmptyCheck($validation_plugins, 'No plugins available for validation. See the TFA help documentation for setup.');
    $encryption_profiles_empty = $this->dataEmptyCheck($encryption_profiles, 'No Encryption profiles available. Please set one up.');

    if ($plugins_empty || $encryption_profiles_empty) {
      $form_state->cleanValues();
      // Return form instead of parent::BuildForm to avoid the save button.
      return $form;
    }

    // Enable TFA checkbox.
    $form['tfa_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable TFA'),
      '#default_value' => $config->get('enabled') && !empty($encryption_profiles),
      '#description' => $this->t('Enable TFA for account authentication.'),
      '#disabled' => empty($encryption_profiles),
    ];

    $enabled_state = [
      'visible' => [
        ':input[name="tfa_enabled"]' => ['checked' => TRUE],
      ],
    ];

    $form['tfa_required_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles required to set up TFA'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
      '#default_value' => $config->get('required_roles') ?: [],
      '#description' => $this->t('Require users with these roles to set up TFA'),
      '#states' => $enabled_state,
      '#required' => FALSE,
    ];

    if (count($validation_plugins)) {
      $form['tfa_allowed_validation_plugins'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Allowed Validation plugins'),
        '#options' => $validation_plugins_labels,
        '#default_value' => $config->get('allowed_validation_plugins') ?: ['tfa_totp'],
        '#description' => $this->t('Plugins that can be setup by users for various TFA processes.'),
        // Show only when TFA is enabled.
        '#states' => $enabled_state,
        '#required' => TRUE,
      ];
      $form['tfa_validate'] = [
        '#type' => 'select',
        '#title' => $this->t('Default Validation plugin'),
        '#options' => $validation_plugins_labels,
        '#default_value' => $config->get('default_validation_plugin') ?: 'tfa_totp',
        '#description' => $this->t('Plugin that will be used as the default TFA process.'),
        // Show only when TFA is enabled.
        '#states' => $enabled_state,
        '#required' => TRUE,
      ];
    }
    else {
      $form['no_validate'] = [
        '#value' => 'markup',
        '#markup' => $this->t('No available validation plugins available. TFA
        process will not occur.'),
      ];
    }

    if (count($validation_plugins_fallbacks)) {
      $form['tfa_fallback'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Validation fallback plugins'),
        '#description' => $this->t('Fallback plugins and order.'),
        '#states' => $enabled_state,
        '#tree' => TRUE,
      ];

      $enabled_fallback_plugins = $config->get('fallback_plugins');
      foreach ($validation_plugins_fallbacks as $plugin => $fallbacks) {
        $fallback_state = [
          'visible' => [
            ':input[name="tfa_validate"]' => ['value' => $plugin],
          ],
        ];
        if (count($fallbacks)) {
          foreach ($fallbacks as $fallback) {
            $order = (@$enabled_fallback_plugins[$plugin][$fallback]['weight']) ?: -2;
            $fallback_value = (@$enabled_fallback_plugins[$plugin][$fallback]['enable']) ?: 1;
            $fallback_instance = $this->tfaValidation->createInstance($fallback, ['uid' => $uid]);
            $form['tfa_fallback'][$plugin][$fallback] = [
              'enable' => [
                '#title' => $fallback_plugins_labels[$fallback],
                '#type' => 'checkbox',
                '#default_value' => $fallback_value,
                '#states' => $fallback_state,
              ],
              'settings' => $fallback_instance->buildConfigurationForm($config, $fallback_state),
              'weight' => [
                '#type' => 'weight',
                '#title' => $this->t('Order'),
                '#delta' => 2,
                '#default_value' => $order,
                '#title_display' => 'invisible',
                '#states' => $fallback_state,
              ],
            ];
          }
        }
        else {
          $form['tfa_fallback'][$plugin] = [
            '#type' => 'item',
            '#description' => $this->t('No fallback plugins available.'),
            '#states' => $fallback_state,
          ];
        }
      }
    }

    // Validation plugin related settings.
    // $validation_plugins_labels has the plugin ids as the key.
    $form['validation_plugin_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extra Settings'),
      '#descrption' => $this->t('Extra plugin settings.'),
      '#tree' => TRUE,
      '#states' => $enabled_state,
    ];
    foreach($validation_plugins_labels as $key => $val) {
      $instance = $this->tfaValidation->createInstance($key, [
        'uid' => $this->currentUser()->id()
        ]
      );

      if(method_exists($instance, 'buildConfigurationForm')) {
        $validation_enabled_state = [
          'visible' => [
            [
              ':input[name="tfa_enabled"]' => ['checked' => TRUE],
              ':input[name="tfa_allowed_validation_plugins[' . $key . ']"]' => ['checked' => TRUE],
            ],
            [
              'select[name="tfa_validate"]' => ['value' => $key],
            ],
          ],
        ];
        $form['validation_plugin_settings'][$key . '_container'] = [
          '#type' => 'container',
          '#states' => $validation_enabled_state,
        ];
        $form['validation_plugin_settings'][$key . '_container']['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $val,
        ];
        $form['validation_plugin_settings'][$key . '_container']['form'] = $instance->buildConfigurationForm($config, $validation_enabled_state);
        $form['validation_plugin_settings'][$key . '_container']['form']['#parents'] = ['validation_plugin_settings', $key];
      }
    }

    // The encryption profiles select box.
    $encryption_profile_labels = [];
    foreach ($encryption_profiles as $encryption_profile) {
      $encryption_profile_labels[$encryption_profile->id()] = $encryption_profile->label();
    }
    $form['encryption_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption Profile'),
      '#options' => $encryption_profile_labels,
      '#description' => 'Encryption profiles to encrypt the secret',
      '#default_value' => $config->get('encryption'),
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];

    $form['validation_skip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skip Validation'),
      '#default_value' => ($config->get('validation_skip')) ?: 2,
      '#description' => 'No. of times a user without having setup tfa validation can login.',
      '#size' => 2,
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];

    // Enable login plugins.
    if (count($login_plugins)) {
      $login_form_array = [];

      foreach ($login_plugins as $login_plugin) {
        $id = $login_plugin['id'];
        $title = $login_plugin['label']->render();
        $login_form_array[$id] = (string) $title;
      }

      $form['tfa_login'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Login plugins'),
        '#options' => $login_form_array,
        '#default_value' => ($config->get('login_plugins')) ? $config->get('login_plugins') : [],
        '#description' => $this->t('Plugins that can allow a user to skip the
        TFA process. If any plugin returns true the user will not be required
        to follow TFA. <strong>Use with caution.</strong>'),
      ];
    }

    // Enable send plugins.
    if (count($send_plugins)) {
      $send_form_array = [];

      foreach ($send_plugins as $send_plugin) {
        $id = $send_plugin['id'];
        $title = $send_plugin['label']->render();
        $send_form_array[$id] = (string) $title;
      }

      $form['tfa_send'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Send plugins'),
        '#options' => $send_form_array,
        '#default_value' => ($config->get('send_plugins')) ? $config->get('send_plugins') : [],
        '#description' => $this->t('TFA Send Plugins, like TFA Twilio'),
      ];
    }

    $form['tfa_flood'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('TFA Flood Settings'),
      '#description' => $this->t('Configure the TFA Flood Settings.'),
    ];

    // Flood control identifier.
    $form['tfa_flood']['tfa_flood_uid_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flood Control With UID Only'),
      '#default_value' => ($config->get('tfa_flood_uid_only')) ?: 0,
      '#description' => $this->t('Flood control on the basis of uid only.'),
    ];

    // Flood window. Defaults to 5mins.
    $form['tfa_flood']['tfa_flood_window'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TFA Flood Window'),
      '#default_value' => ($config->get('tfa_flood_window')) ?: 300,
      '#description' => 'TFA Flood Window.',
      '#size' => 5,
      '#states' => $enabled_state,
      '#required' => TRUE,
    ];

    // Flood threshold. Defaults to 6 failed attempts.
    $form['tfa_flood']['tfa_flood_threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TFA Flood Threshold'),
      '#default_value' => ($config->get('tfa_flood_threshold')) ?: 6,
      '#description' => 'TFA Flood Threshold.',
      '#size' => 2,
      '#required' => TRUE,
    ];

    // Email configurations.
    if ($config->get('mail') === NULL) {
      $message = $this->t('Email settings missing. If this is the first time you are seeing this error after upgrading the TFA module, then please make sure you have run the required @update_link function.', [
        '@update_link' => Link::createFromRoute('update', 'system.status')->toString(),
      ]);
      drupal_set_message($message, 'error');
    }
    $form['mail'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#default_tab' => 'edit-tfa-enabled-configuration',
    ];
    $form['tfa_enabled_configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('User enabled TFA validation method'),
      '#description' => $this->t('This email is sent to the user when they enable a TFA validation method on their account. Available tokens are: [site] and [user]. Common variables are: [site:name], [site:url], [user:display-name], [user:account-name], and [user:mail].'),
      '#group' => 'mail',
      'tfa_enabled_configuration_subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get('mail.tfa_enabled_configuration.subject'),
        '#required' => TRUE,
      ],
      'tfa_enabled_configuration_body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get('mail.tfa_enabled_configuration.body'),
        '#required' => TRUE,
        '#attributes' => [
          'rows' => 10,
        ],
      ],
    ];
    $form['tfa_disabled_configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('User disabled TFA validation method'),
      '#description' => $this->t('This email is sent to the user when they disable a TFA validation method on their account. Available tokens are: [site] and [user]. Common variables are: [site:name], [site:url], [user:display-name], [user:account-name], and [user:mail].'),
      '#group' => 'mail',
      'tfa_disabled_configuration_subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get('mail.tfa_disabled_configuration.subject'),
        '#required' => TRUE,
      ],
      'tfa_disabled_configuration_body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get('mail.tfa_disabled_configuration.body'),
        '#required' => TRUE,
        '#attributes' => [
          'rows' => 10,
        ],
      ],
    ];
    $form['help_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('Text to display when a user is locked out and blocked from logging in.'),
      '#default_value' => $config->get('help_text'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $validation_plugin = $form_state->getValue('tfa_validate');
    $allowed_validation_plugins = $form_state->getValue('tfa_allowed_validation_plugins');
    // Default validation plugin must always be allowed.
    $allowed_validation_plugins[$validation_plugin] = $validation_plugin;
    $fallback_plugins = $form_state->getValue('tfa_fallback');
    if (empty($fallback_plugins)) {
      $fallback_plugins = [];
    }
    $validation_plugin_settings = $form_state->getValue('validation_plugin_settings');
    if (empty($validation_plugin_settings)) {
      $validation_plugin_settings = [];
    }

    // Delete tfa data if plugin is disabled.
    if ($this->config('tfa.settings')->get('enabled') && !$form_state->getValue('tfa_enabled')) {
      $this->userData->delete('tfa');
    }

    $send_plugins = $form_state->getValue('tfa_send') ?: [];
    $login_plugins = $form_state->getValue('tfa_login') ?: [];
    $this->config('tfa.settings')
      ->set('enabled', $form_state->getValue('tfa_enabled'))
      ->set('required_roles', $form_state->getValue('tfa_required_roles'))
      ->set('send_plugins', array_filter($send_plugins))
      ->set('login_plugins', array_filter($login_plugins))
      ->set('allowed_validation_plugins', array_filter($allowed_validation_plugins))
      ->set('default_validation_plugin', $validation_plugin)
      ->set('validation_plugin_settings', $validation_plugin_settings)
      ->set('fallback_plugins', $fallback_plugins)
      ->set('validation_skip', $form_state->getValue('validation_skip'))
      ->set('encryption', $form_state->getValue('encryption_profile'))
      ->set('tfa_flood_uid_only', $form_state->getValue('tfa_flood_uid_only'))
      ->set('tfa_flood_window', $form_state->getValue('tfa_flood_window'))
      ->set('tfa_flood_threshold', $form_state->getValue('tfa_flood_threshold'))
      ->set('mail.tfa_enabled_configuration.subject', $form_state->getValue('tfa_enabled_configuration_subject'))
      ->set('mail.tfa_enabled_configuration.body', $form_state->getValue('tfa_enabled_configuration_body'))
      ->set('mail.tfa_disabled_configuration.subject', $form_state->getValue('tfa_disabled_configuration_subject'))
      ->set('mail.tfa_disabled_configuration.body', $form_state->getValue('tfa_disabled_configuration_body'))
      ->set('help_text', $form_state->getValue('help_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tfa.settings'];
  }

  /**
   * Check whether the given data is empty and set appropritate message.
   *
   * @param array $data
   *   Data to be checked.
   * @param string $message
   *   Message to show if data is empty.
   *
   * @return bool
   *   TRUE if data is empty otherwise FALSE.
   */
  protected function dataEmptyCheck($data, $message) {
    if (empty($data)) {
      drupal_set_message($this->t($message), 'error');
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Resets TFA settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('tfa.settings.reset');
  }

}
