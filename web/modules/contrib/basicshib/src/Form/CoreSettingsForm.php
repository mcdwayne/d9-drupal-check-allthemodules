<?php

namespace Drupal\basicshib\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class CoreSettingsForm.
 */
class CoreSettingsForm extends ConfigFormBase {

  /**
   * Constructs a new CoreSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'basicshib.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'core_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('basicshib.settings');

    $form['default_post_login_redirect_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default post-login redirect path'),
      '#description' => $this->t('The location that the user will be redirected to after shibboleth login, when no location is specified by the return URL.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('default_post_login_redirect_path'),
    ];
    $form['login_handler'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login handler'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('handlers')['login'],
    ];

    $form['logout_handler'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logout handler'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('handlers')['logout'],
    ];

    $form['attributes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Attributes'),
    ];

    $form['attributes']['name_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username attribute'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('attribute_map')['key']['name'],
    ];

    $form['attributes']['mail_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mail attribute'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('attribute_map')['key']['mail'],
    ];

    $form['attributes']['session_id_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Session attribute'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('attribute_map')['key']['session_id'],
    ];

    $form['ui'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User interface'),
    ];

    $form['ui']['login_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login link label'),
      '#description' => $this->t('The label of the login link'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('login_link_label'),
    ];

    $form['ui']['external_redirect_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('External redirect error message'),
      '#description' => $this->t('The message to display when an attempt to redirect to an external URL occurs. This may be malicious so contact information for reporting the incident is good to have here.'),
      '#maxlength' => 1024,
      '#rows' => 3,
      '#default_value' => $config->get('messages')['external_redirect_error'],
    ];

    $form['ui']['generic_login_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Generic login error'),
      '#description' => $this->t('The message to display when login fails due to an error which cannot be disclosed to the browser.'),
      '#maxlength' => 1024,
      '#rows' => 3,
      '#default_value' => $config->get('messages')['generic_login_error'],
    ];

    $form['ui']['account_blocked_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Account blocked error'),
      '#description' => $this->t('The message to display when a user can be authenticated with Shibboleth, but is blocked by Drupal.'),
      '#maxlength' => 1024,
      '#rows' => 3,
      '#default_value' => $config->get('messages')['account_blocked_error'],
    ];

    $form['ui']['login_disallowed_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login disallowed error'),
      '#description' => $this->t('The message to display when a user login is disallowed for a reason other than being blocked.'),
      '#maxlength' => 1024,
      '#rows' => 3,
      '#default_value' => $config->get('messages')['login_disallowed_error'],
    ];

    $form['ui']['user_creation_not_allowed_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User creation disallowed error'),
      '#description' => $this->t('The message to display when a user cannot be created.'),
      '#maxlength' => 1024,
      '#rows' => 3,
      '#default_value' => $config->get('messages')['user_creation_not_allowed_error'],
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

    $attribute_map = $this->config('basicshib.settings')
      ->get('attribute_map');

    $attribute_map['key'] = [
      'name' => $form_state->getValue('name_attribute'),
      'mail' => $form_state->getValue('mail_attribute'),
      'session_id' => $form_state->getValue('session_id_attribute'),
    ];

    $this->config('basicshib.settings')
      ->set('login_link_label', $form_state->getValue('login_link_label'))
      ->set('default_post_login_redirect_path', $form_state->getValue('default_post_login_redirect_path'))
      ->set('handlers', [
          'login' => $form_state->getValue('login_handler'),
          'logout' => $form_state->getValue('logout_handler'),
        ])
      ->set('messages', [
          'external_redirect_error' => $form_state->getValue('external_redirect_error'),
          'generic_login_error' => $form_state->getValue('generic_login_error'),
          'account_blocked_error' => $form_state->getValue('account_blocked_error'),
          'login_disallowed_error' => $form_state->getValue('login_disallowed_error'),
          'user_creation_not_allowed_error' => $form_state->getValue('user_creation_not_allowed_error'),
        ])
      ->set('attribute_map', $attribute_map)
      ->save();
  }

}
