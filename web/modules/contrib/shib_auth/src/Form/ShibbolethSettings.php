<?php

namespace Drupal\shib_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShibbolethSettings.
 *
 * @package Drupal\shib_auth\Form
 */
class ShibbolethSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shib_auth.shibbolethsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shibboleth_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shib_auth.shibbolethsettings');
    $form['shibboleth_handler_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Shibboleth Handler Settings'),
      '#open' => 'open',
    ];
    $form['shibboleth_handler_settings']['shibboleth_login_handler_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shibboleth login handler URL'),
      '#description' => $this->t('The URL can be absolute or relative to the server base url: http://www.example.com/Shibboleth.sso/DS; /Shibboleth.sso/DS'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('shibboleth_login_handler_url'),
    ];
    $form['shibboleth_handler_settings']['shibboleth_logout_handler_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shibboleth logout handler URL'),
      '#description' => $this->t('The URL can be absolute or relative to the server base url: http://www.example.com/Shibboleth.sso/Logout; /Shibboleth.sso/Logout'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('shibboleth_logout_handler_url'),
    ];
    $form['shibboleth_handler_settings']['shibboleth_login_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shibboleth login link text'),
      '#description' => $this->t('The text of the login link. You can change this text on the Shibboleth login block settings form too!'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('shibboleth_login_link_text'),
    ];
    $form['shibboleth_handler_settings']['force_https_on_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force HTTPS on login'),
      '#description' => $this->t('The user will be redirected to HTTPS'),
      '#default_value' => $config->get('force_https_on_login'),
    ];
    $form['attribute_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Attribute Settings'),
      '#open' => 'open',
    ];
    $form['attribute_settings']['server_variable_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server variable for username'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('server_variable_username'),
    ];
    $form['attribute_settings']['server_variable_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server variable for e-mail address'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('server_variable_email'),
    ];
    // $form['attribute_settings']['user_defined_usernames'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('User-defined usernames'),
    //      '#description' => $this->t('Allow users to set their Drupal usernames at first Shibboleth login. Note that disabling this option only prevents new users from registering their own username. Existing user-defined usernames will remain valid.'),
    //      '#default_value' => $config->get('user_defined_usernames'),
    //    ];
    //    $form['attribute_settings']['user_defined_email'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('User-defined email addresses'),
    //      '#description' => $this->t('Ask users to set their contact email address at first login. Disabling this option will override contact address with the one, which was received from IdP. (In this case, missing e-mail address will result in fatal error.)'),
    //      '#default_value' => $config->get('user_defined_email'),
    //    ];
    //    $form['attribute_settings']['account_linking'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('Account Linking'),
    //      '#description' => $this->t('Allow locally authenticated users to link their Drupal accounts to federated logins. Note that disabling this option only prevents from creating/removing associations, existing links will remain valid.'),
    //      '#default_value' => $config->get('account_linking'),
    //    ];
    //    $form['attribute_settings']['shibboleth_account_linking_text'] = [
    //      '#type' => 'textfield',
    //      '#title' => $this->t('Shibboleth account linking text'),
    //      '#description' => $this->t('The text of the link providing account linking shown on the user settings form.'),
    //      '#maxlength' => 128,
    //      '#size' => 64,
    //      '#default_value' => $config->get('shibboleth_account_linking_text'),
    //    ];.
    $form['debugging_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Debugging Options'),
      '#open' => 'open',
    ];
    $form['debugging_options']['enable_debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable DEBUG mode.'),
      '#default_value' => $config->get('enable_debug_mode'),
    ];
    $form['debugging_options']['debug_prefix_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DEBUG path prefix'),
      '#description' => $this->t("For example, use \"/user/\" to display DEBUG messages on paths like \"/user/*\"."),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('debug_prefix_path'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('shib_auth.shibbolethsettings')
      ->set('shibboleth_login_handler_url', $form_state->getValue('shibboleth_login_handler_url'))
      ->set('shibboleth_logout_handler_url', $form_state->getValue('shibboleth_logout_handler_url'))
      ->set('shibboleth_login_link_text', $form_state->getValue('shibboleth_login_link_text'))
      ->set('force_https_on_login', $form_state->getValue('force_https_on_login'))
      ->set('server_variable_username', $form_state->getValue('server_variable_username'))
      ->set('server_variable_email', $form_state->getValue('server_variable_email'))
    // ->set('user_defined_usernames', $form_state->getValue('user_defined_usernames'))
    //      ->set('user_defined_email', $form_state->getValue('user_defined_email'))
    //      ->set('account_linking', $form_state->getValue('account_linking'))
    //      ->set('shibboleth_account_linking_text', $form_state->getValue('shibboleth_account_linking_text'))
      ->set('enable_debug_mode', $form_state->getValue('enable_debug_mode'))
      ->set('debug_prefix_path', $form_state->getValue('debug_prefix_path'))
      ->save();

    // Invalidate the cache for the Shib login block.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['shibboleth_login_block']);

  }

}
