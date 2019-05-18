<?php

namespace Drupal\oauth2_jwt_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define OAuth2 JWT SSO Settings Form.
 *
 * @package Drupal\oauth2_jwt_sso\Form
 */
class OAuth2JwtSSOSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oauth2_jwt_sso_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $oauth2_jwt_sso_config = $this->config('oauth2_jwt_sso.settings');

    $form['authorization_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Authorization URL'),
      '#default_value' => $oauth2_jwt_sso_config->get('authorization_url'),
      '#required' => TRUE,
    ];
    $form['access_token_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Access Token URL'),
      '#default_value' => $oauth2_jwt_sso_config->get('access_token_url'),
      '#required' => TRUE,
    ];
    $form['logout_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Logout URL'),
      '#default_value' => $oauth2_jwt_sso_config->get('logout_url'),
      '#required' => TRUE,
    ];
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $oauth2_jwt_sso_config->get('client_id'),
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $oauth2_jwt_sso_config->get('client_secret'),
      '#required' => TRUE,
    ];
    $form['auth_public_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Auth Server Public Key'),
      '#default_value' => $oauth2_jwt_sso_config->get('auth_public_key'),
      '#required' => TRUE,
    ];
    $form['roles_remote_login'] = [
      '#type' => 'checkboxes',
      '#options' => user_role_names(TRUE),
      '#title' => $this->t('Select roles that using remote login'),
      '#default_value' => !($oauth2_jwt_sso_config->get('roles_remote_login')) ? [] : $oauth2_jwt_sso_config->get('roles_remote_login'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('oauth2_jwt_sso.settings')
      ->set('authorization_url', $values['authorization_url'])
      ->set('access_token_url', $values['access_token_url'])
      ->set('logout_url', $values['logout_url'])
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('auth_public_key', $values['auth_public_key'])
      ->set('roles_remote_login', array_filter($values['roles_remote_login']))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['oauth2_jwt_sso.settings'];
  }

}
