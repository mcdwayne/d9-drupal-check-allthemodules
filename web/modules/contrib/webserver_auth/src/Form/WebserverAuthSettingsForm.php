<?php

namespace Drupal\webserver_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for changing optional Webserver Auth settings.
 */
class WebserverAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webserver_auth_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webserver_auth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webserver_auth.settings');

    $form['create_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create user when user does not exist in the Drupal user table'),
      '#default_value' => $config->get('create_user'),
      '#description' => $this->t('If this option is disabled, a user that does not exist in Drupal is considered an anonymous user'),
    ];

    $form['email_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email domain'),
      '#default_value' => $config->get('email_domain'),
      '#size' => 30,
      '#maxlength' => 55,
      '#description' => $this->t('Append this domain name to each new user in order to generate their email address.'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['advanced']['disallow_username_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable option to change username'),
      '#default_value' => $config->get('disallow_username_change'),
      '#description' => $this->t('Disable the option for users to change their username.  This is most useful when the web server is already authenticating against an external database.'),
    ];

    $form['advanced']['disallow_pw_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove option to change password'),
      '#default_value' => $config->get('disallow_pw_change'),
      '#description' => $this->t('Remove the option for users to change their password.  This is most useful when the web server is already authenticating against an external database. This also removes the password validation requirement to change email addresses.'),
    ];

    $form['advanced']['site_access'] = [
      '#type' => 'details',
      '#title' => $this->t('Access to the Site'),
      '#open' => TRUE,
    ];

    $form['advanced']['site_access']['block_access_to_the_site'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Block access to the site if webserver username is not provided'),
      '#default_value' => $config->get('block_access_to_the_site'),
      '#description' => $this->t('Anonymous user won\'t have access to the site at all if checked.'),
    ];

    $form['advanced']['site_access']['block_access_redirect_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL redirect if access is blocked'),
      '#default_value' => $config->get('block_access_redirect_url'),
      '#description' => $this->t('Provide optional absolute URL where blocked users should be redirected to. URL should start with http:// or https://'),
    ];

    $form['advanced']['strip_prefix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip prefix'),
      '#default_value' => $config->get('strip_prefix'),
      '#description' => $this->t('Strip NTLM-style prefixes (e.g. \'foo1\foo2\') from the login name (\'foo1\foo2\bar\') to generate the username (\'bar\').'),
    ];

    $form['advanced']['strip_domain'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip domain'),
      '#default_value' => $config->get('strip_domain'),
      '#description' => $this->t('Strip a domain name (e.g. \'@EXAMPLE.COM\') from the login name (\'newuser@EXAMPLE.COM\') to generate the username (\'newuser\').'),
    ];

    $form['advanced']['login_url'] = [
      '#type' => 'textfield',
      '#size' => 75,
      '#maxlength' => 1024,
      '#title' => $this->t('Login URL'),
      '#default_value' => $config->get('login_url'),
      '#description' => $this->t('Custom login URL. An empty URL disables the login link on anonymous user pages, any other value will be used as the login URL.'),
    ];

    $form['advanced']['logout_url'] = [
      '#type' => 'textfield',
      '#size' => 75,
      '#maxlength' => 1024,
      '#title' => $this->t('Logout URL'),
      '#default_value' => $config->get('logout_url'),
      '#description' => $this->t('Custom logout URL. An empty URL disables the logout link on authenticated pages, any other value will be used as the logout URL.'),
    ];

    $form['advanced']['register_url'] = [
      '#type' => 'textfield',
      '#size' => 75,
      '#maxlength' => 1024,
      '#title' => $this->t('Register URL'),
      '#default_value' => $config->get('register_url'),
      '#description' => $this->t('Custom register URL. An empty URL disables the logout link on authenticated pages, any other value will be used as the logout URL.'),
    ];

    // @todo work on other options.

//
//    $form['match_existing'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Match external names to existing Drupal users'),
//      '#default_value' => $config->get('match_existing'),
//      '#description' => $this->t('Match against the usernames of existing Drupal users that weren\'t created by this module when validating logins. Disable if you want to manage authentication module mappings manually.'),
//    ];
//
//    $form['advanced']['add_all_new'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Register manually created new users'),
//      '#default_value' => $config->get('add_all_new'),
//      '#description' => $this->t('By default new users created outside this module will not be able to login using this module. Checking this option allows all new users created by any means to login via this module.  Only applies to newly created users.'),
//    ];
//
//    $form['advanced']['skip_check'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Skip authorisation table check'),
//      '#default_value' => $config->get('skip_check'),
//      '#description' => $this->t('Skips the authorisation check, allowing users to login even if they were not created though this module. Not recommended if you use multiple authentication methods.'),
//    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('webserver_auth.settings')
      ->set('create_user', $values['create_user'])
      ->set('email_domain', $values['email_domain'])
      ->set('match_existing', $values['match_existing'])
      ->set('strip_prefix', $values['strip_prefix'])
      ->set('strip_domain', $values['strip_domain'])
      ->set('disallow_pw_change', $values['disallow_pw_change'])
      ->set('disallow_username_change', $values['disallow_username_change'])
      ->set('login_url', $values['login_url'])
      ->set('logout_url', $values['logout_url'])
      ->set('register_url', $values['register_url'])
      ->set('account_modification', $values['account_modification'])
      ->set('add_all_new', $values['add_all_new'])
      ->set('skip_check', $values['skip_check'])
      ->set('block_access_to_the_site', $values['block_access_to_the_site'])
      ->set('block_access_redirect_url', $values['block_access_redirect_url'])
      ->save();
  }

}
