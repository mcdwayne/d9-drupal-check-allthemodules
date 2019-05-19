<?php

namespace Drupal\social_connect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Social connect configuration form.
 */
class AdminSettings extends ConfigFormBase {

  /**
   * Social connect settings.
   *
   * @var settings
   */
  private $settings;

  /**
   * Global settings for Social Connect.
   *
   * @var globalSettings
   */
  private $globalSettings;

  /**
   * All connection settings.
   *
   * @var connections
   */
  private $connections;

  /**
   * Facebook settings.
   *
   * @var FbSettings
   */
  private $FbSettings;

  /**
   * Google settings.
   *
   * @var googleSettings
   */
  private $googleSettings;

  /**
   * Determines the ID of a form.
   */
  public function getFormId() {
    return 'social_connect_admin_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   */
  public function getEditableConfigNames() {
    return [
      'social_connect.settings'
    ];
  }

  /**
   * AdminSettings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $configs = $this->config('social_connect.settings');
    $this->settings = $configs;
    $this->globalSettings = $configs->get('global');
    $this->connections = $configs->get('connections');
    $this->FbSettings = $this->connections['facebook'];
    $this->googleSettings = $this->connections['google'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Global settings.
    $form['global_settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Global settings'),
      '#open' => FALSE
    ];

    $form['global_settings_fieldset']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('If checked - the app will debug and print debug message in console (Advised to turn it off on production environment).'),
      '#default_value' => $this->globalSettings['debug']
    ];
    $form['global_settings_fieldset']['mail_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mail notify'),
      '#description' => $this->t('If checked - the app will send mail to user on registration.'),
      '#default_value' => $this->globalSettings['mail_notify']
    ];

    $form['global_settings_fieldset']['redirect_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect to'),
      '#description' => $this->t('Enter absolute/relative url to redirect user after login, by default it will on same page.'),
      '#default_value' => $this->globalSettings['redirect_to']
    ];

    // Login page settings.
    $form['global_settings_fieldset']['login_page_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login Page Settings')
    ];

    // Add social login in login form
    $form['global_settings_fieldset']['login_page_settings_fieldset']['show_on_signin_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show icon(s) on login form'),
      '#description' => $this->t('If checked - social icons will appear on login form.'),
      '#default_value' => $this->globalSettings['show_on_signin_form']
    ];

    $form['global_settings_fieldset']['login_page_settings_fieldset']['login_page_icons'] = [
      '#type' => 'select',
      '#title' => $this->t('Social connect icons'),
      '#description' => $this->t('Allows the users to login either with their social network account or with their already existing account.'),
      '#options' => [
        'above' => $this->t('Show the icon(s) above the existing login form (Default, recommended)'),
        'below' => $this->t('Show the icon(s) below the existing login form'),
        'disable' => $this->t('Do not show the icons on the login page')
      ],
      '#default_value' => $this->globalSettings['login_page_icons']
    ];

    $form['global_settings_fieldset']['login_page_settings_fieldset']['login_page_above_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Above caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['login_page_above_caption'],
      '#description' => $this->t('This will be displayed above the social network icons. (You can put with HTML tags)')
    ];

    $form['global_settings_fieldset']['login_page_settings_fieldset']['login_page_below_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Below caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['login_page_below_caption'],
      '#description' => $this->t('This is the title displayed below the social network icons. (You can put with HTML tags)')
    ];

    // Registration page settings.
    $form['global_settings_fieldset']['registration_page_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Registration Page Settings')
    ];

    // Add social login in login form
    $form['global_settings_fieldset']['registration_page_settings_fieldset']['show_on_signup_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show icon(s) on registration form'),
      '#description' => $this->t('If checked - social icons will appear on registration form.'),
      '#default_value' => $this->globalSettings['show_on_signup_form']
    ];

    $form['global_settings_fieldset']['registration_page_settings_fieldset']['registration_page_icons'] = [
      '#type' => 'select',
      '#title' => $this->t('Social Login Icons'),
      '#description' => $this->t('Allows the users to register by using either their social network account or by creating a new account.'),
      '#options' => [
        'above' => $this->t('Show the icons above the existing registration form (Default, recommended)'),
        'below' => $this->t('Show the icons below the existing registration form'),
        'disable' => $this->t('Do not show the icons on the registration page')
      ],
      '#default_value' => $this->globalSettings['registration_page_icons']
    ];

    $form['global_settings_fieldset']['registration_page_settings_fieldset']['registration_page_above_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Above caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['registration_page_above_caption'],
      '#description' => $this->t('This is the title displayed above the social network icons. (You can put with HTML tags)')
    ];

    $form['global_settings_fieldset']['registration_page_settings_fieldset']['registration_page_below_caption'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Below caption [Leave empty for none]'),
      '#default_value' => $this->globalSettings['registration_page_below_caption'],
      '#description' => $this->t('This is the title displayed below the social network icons. (You can put with HTML tags)')
    ];

    // Facebook settings
    $this->getFacebookSettings($form, $form_state);
    // Google plus settings
    $this->getGooglePlusSettings($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configurations')
    ];
    return $form;
  }

  /*
   * Facebook settings.
   */

  private function getFacebookSettings(array &$form, FormStateInterface $form_state) {
    $form['facebook_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Facebook connect'),
      '#default_value' => $this->FbSettings['enable']
    ];

    $form['facebook_settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook settings'),
      '#open' => FALSE,
      '#description' => $this->t('Your Facebook application settings.<br />You need to get application ID and secret from <a target="_blank" href="https://developers.facebook.com/">Facebook website</a>.'),
      '#states' => [
        'visible' => [
          'input[name="facebook_enable"]' => ['checked' => TRUE]
        ]
      ]
    ];

    $form['facebook_settings_fieldset']['facebook_app_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook application ID'),
      '#default_value' => $this->FbSettings['app_id'],
      '#states' => [
        'required' => [
          'input[name="facebook_enable"]' => ['checked' => TRUE]
        ]
      ]
    ];

    $form['facebook_settings_fieldset']['facebook_api_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook API version'),
      '#description' => $this->t('e.g v2.11/v2.12'),
      '#default_value' => $this->FbSettings['api_version']
    ];

    $form['facebook_settings_fieldset']['facebook_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom login button text'),
      '#default_value' => $this->FbSettings['button_text']
    ];

    $form['facebook_settings_fieldset']['facebook_picture_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Picture size'),
      '#default_value' => $this->FbSettings['picture_size'],
      '#options' => [
        'small' => $this->t('Small (50 px wide, variable height)'),
        'normal' => $this->t('Normal (100 px wide, variable height)'),
        'album' => $this->t('Album'),
        'large' => $this->t('Large (About 200 px wide, variable height)'),
        'square' => $this->t('Square (50x50)')
      ]
    ];

    $form['facebook_settings_fieldset']['facebook_picture_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override profile picture'),
      '#description' => $this->t('If checked - user profile picture will we overriden with facebook profile picture.'),
      '#default_value' => $this->FbSettings['picture_override']
    ];
  }

  /*
   * Google plus settings.
   */

  private function getGooglePlusSettings(array &$form, FormStateInterface $form_state) {
    $form['google_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Google Plus connect'),
      '#default_value' => $this->googleSettings['enable']
    ];

    $form['google_settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Google plus settings'),
      '#open' => FALSE,
      '#description' => $this->t('Your Goole plus application settings.<br />You need to get client ID and secret from <a target="_blank" href="https://console.developers.google.com/">Google website</a>.'),
      '#states' => [
        'visible' => [
          'input[name="google_enable"]' => ['checked' => TRUE]
        ]
      ]
    ];

    $form['google_settings_fieldset']['google_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google client ID'),
      '#default_value' => $this->googleSettings['client_id'],
      '#states' => [
        'required' => [
          'input[name="google_enable"]' => ['checked' => TRUE]
        ]
      ]
    ];

    $form['google_settings_fieldset']['google_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom login button text'),
      '#default_value' => $this->googleSettings['button_text']
    ];

    $form['google_settings_fieldset']['google_picture_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Picture size'),
      '#default_value' => $this->googleSettings['picture_size'],
      '#options' => [
        'default' => $this->t('Default'),
        'small' => $this->t('Small (50 px wide)'),
        'normal' => $this->t('Normal (100 px wide)'),
        'large' => $this->t('Large (About 200 px wide)')
      ],
    ];

    $form['google_settings_fieldset']['google_picture_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override profile picture'),
      '#description' => $this->t('If checked - user profile picture will we overriden with google plus profile picture.'),
      '#default_value' => $this->googleSettings['picture_override']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // global settings
    $global = [
      'debug' => $values['debug'],
      'login_page_icons' => $values['login_page_icons'],
      'login_page_above_caption' => $values['login_page_above_caption'],
      'login_page_below_caption' => $values['login_page_below_caption'],
      'registration_page_icons' => $values['registration_page_icons'],
      'registration_page_above_caption' => $values['registration_page_above_caption'],
      'registration_page_below_caption' => $values['registration_page_below_caption'],
      'show_on_signin_form' => $values['show_on_signin_form'],
      'show_on_signup_form' => $values['show_on_signup_form'],
      'mail_notify' => $values['mail_notify'],
      'redirect_to' => $values['redirect_to']
    ];

    // connection settings
    $connections = [
      'facebook' => [
        'enable' => $values['facebook_enable'],
        'app_id' => $values['facebook_app_key'],
        'api_version' => $values['facebook_api_version'],
        'button_text' => $values['facebook_button_text'],
        'picture_size' => $values['facebook_picture_size'],
        'picture_override' => $values['facebook_picture_override'],
        'field_maps' => []
      ],
      'google' => [
        'enable' => $values['google_enable'],
        'client_id' => $values['google_client_id'],
        'button_text' => $values['google_button_text'],
        'picture_size' => $values['google_picture_size'],
        'picture_override' => $values['google_picture_override'],
        'field_maps' => []
      ]
    ];


    $this->config('social_connect.settings')
        ->set('global', $global)
        ->set('connections', $connections)
        ->save();

    parent::submitForm($form, $form_state);
  }

}
