<?php

namespace Drupal\token_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TokenLoginConfiguration.
 *
 * @package Drupal\token_login\Form
 */
class TokenLoginConfiguration extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'token_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'token_login_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('token_login.settings');
    $user_config = $this->config('user.settings');
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login email'),
      '#description' => $this->t('Email message to be sent out. Use [user:one-time-login-url] as the placeholder of the login link.'),
      '#default_value' => $config->get('message'),
    ];
    $form['token_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token lifetime'),
      '#description' => $this->t('How long the token should be usable after generation (in seconds).'),
      '#default_value' => $user_config->get('password_reset_timeout'),
    ];
    $form['allowed_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed domains'),
      '#description' => $this->t('Only email addresses with this domain can use the one-time login tokens.'),
      '#default_value' => $config->get('allowed_domains'),
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

    $this->config('token_login.settings')
      ->set('allowed_domains', $form_state->getValue('allowed_domains'))
      ->set('message', $form_state->getValue('message'))
      ->save();
    $user_settings = \Drupal::service('config.factory')->getEditable('user.settings');
    $user_settings->set('password_reset_timeout', $form_state->getValue('token_lifetime'))->save();
  }

}
