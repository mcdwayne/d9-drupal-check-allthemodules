<?php

namespace Drupal\super_login;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Super Login settings for this site.
 */
class super_loginSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'super_login_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'super_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('super_login.settings');

    $form['text'] = [
      '#type' => 'fieldset',
      '#title' => t('Text String Options'),
    ];

    $form['text']['login_title'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Log In Text'),
      '#description' => $this->t('Enter the text to be displayed above the login form.'),
      '#default_value' => $config->get('super_login.login_title'),
    ];

    $form['text']['forgot_pw_text'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Forgot Password Link Text'),
      '#description' => $this->t("Enter the text to be displayed as the forgot password's link anchor text."),
      '#default_value' => $config->get('super_login.forgot_pw_text'),
    ];

    $form['text']['capslock_msg'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Caps Lock Message'),
      '#description' => $this->t('Enter the text to be displayed when a user tries to enter a password with the caps look on.'),
      '#default_value' => $config->get('super_login.capslock_msg'),
    ];

    $form['text']['new_account_text'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('New Account Text'),
      '#description' => $this->t('Enter the text to be displayed for the new account link.'),
      '#default_value' => $config->get('super_login.new_account_text'),
    ];

    $form['text']['login_text'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Login Title'),
      '#description' => $this->t('Enter the text to be displayed above the username/email login field.'),
      '#default_value' => $config->get('super_login.login_text'),
    ];

    $form['text']['password_reset_title'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Password Reset Title'),
      '#description' => $this->t('Enter the title to be displayed on the password reset page.'),
      '#default_value' => $config->get('super_login.password_reset_title'),
    ];

    $form['text']['back_link'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Back to Login Page Text.'),
      '#description' => $this->t('Enter the text to be displayed for the "back to login page" link.'),
      '#default_value' => $config->get('super_login.back_link'),
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Configuration Options'),
    ];

    $options = [
      0 => t('Username or Email Address'),
      1 => t('Username Only'),
      2 => t('Email Address Only'),
    ];
    $form['options']['login_type'] = [
      '#type'  => 'radios',
      '#options' => $options,
      '#title' => $this->t('Login Type'),
      '#description' => $this->t('Options to allow logging into the site with.'),
      '#default_value' => $config->get('super_login.login_type'),
    ];

    $form['options']['messages'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Show system messages outside of login form'),
      '#description' => $this->t('Disable this option to display system messages in the default/normal Drupal location'),
      '#default_value' => $config->get('super_login.messages'),
    ];

    $form['options']['css'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Enable module CSS (stylesheet)'),
      '#description' => $this->t("Disable this option to turn off this module's CSS style sheet. If disabled, you should provide your own styling through your theme's stylesheet."),
      '#default_value' => $config->get('super_login.css'),
    ];

    $form['options']['capslock'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Enable Caps Lock Warning'),
      '#description' => $this->t('Disable this option to turn off the caps lock warning message for the password field.'),
      '#default_value' => $config->get('super_login.capslock'),
    ];

    $form['options']['button_theme'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Enable replacement of standard submit button with CSS3 theme.'),
      '#disabled' => $config->get('super_login.css') ? FALSE : TRUE,
      '#description' => t('Disable this option to remove the CSS3 theme on the submit buttons.'),
      '#default_value' => $config->get('super_login.button_theme'),
    ];

    $form['options']['autofocus'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Enable autofocus for username field'),
      '#default_value' => $config->get('super_login.autofocus'),
      '#description' => t('Enable this option for browsers to automatically set the focus in username field.'),
    ];

    $form['options']['placeholder'] = [
      '#type'  => 'checkbox',
      '#title' => $this->t('Enable "placeholder" text within fields.'),
      '#description' => $this->t('Disable this option to remove the placeholder text within the login and password reset fields.'),
      '#default_value' => $config->get('super_login.placeholder'),
    ];

    $form['options']['placeholders'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="placeholder"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['options']['placeholders']['login_placeholder'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Login Placeholder'),
      '#description' => $this->t('Enter the text to be displayed as placeholder for the username/email login field.'),
      '#default_value' => $config->get('super_login.login_placeholder'),
    ];

    $form['options']['placeholders']['pass_placeholder'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Password Placeholder'),
      '#description' => $this->t('Enter the text to be displayed as placeholder for the password field.'),
      '#default_value' => $config->get('super_login.pass_placeholder'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('super_login.settings');
    $config->set('super_login.login_text', $form_state->getValue('login_text'));
    $config->set('super_login.forgot_pw_text', $form_state->getValue('forgot_pw_text'));
    $config->set('super_login.capslock_msg', $form_state->getValue('capslock_msg'));
    $config->set('super_login.new_account_text', $form_state->getValue('new_account_text'));
    $config->set('super_login.login_title', $form_state->getValue('login_title'));
    $config->set('super_login.password_reset_title', $form_state->getValue('password_reset_title'));
    $config->set('super_login.back_link', $form_state->getValue('back_link'));
    $config->set('super_login.css', $form_state->getValue('css'));
    $config->set('super_login.messages', $form_state->getValue('messages'));
    $config->set('super_login.button_theme', $form_state->getValue('button_theme'));
    $config->set('super_login.capslock', $form_state->getValue('capslock'));
    $config->set('super_login.placeholder', $form_state->getValue('placeholder'));
    $config->set('super_login.login_type', $form_state->getValue('login_type'));
    $config->set('super_login.login_placeholder', $form_state->getValue('login_placeholder'));
    $config->set('super_login.pass_placeholder', $form_state->getValue('pass_placeholder'));
    $config->set('super_login.autofocus', $form_state->getValue('autofocus'));

    $config->save();

    parent::submitForm($form, $form_state);
    $module_data = system_rebuild_module_data();
  }

}
