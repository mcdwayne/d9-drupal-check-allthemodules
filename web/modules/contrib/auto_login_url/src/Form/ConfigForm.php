<?php

namespace Drupal\auto_login_url\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_login_url_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auto_login_url.settings');

    // Secret word.
    $form['auto_login_url_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret word'),
      '#required' => TRUE,
      '#default_value' => \Drupal::service('auto_login_url.general')->getSecret(),
      '#description' => $this->t('Secret word to create hashes that are stored in DB.
        Every time this changes all previous URLs are invalidated.'),
    ];

    // Expiration.
    $form['auto_login_url_expiration'] = [
      '#title' => $this->t('Expiration'),
      '#required' => TRUE,
      '#default_value' => $config->get('expiration'),
      '#description' => $this->t('Expiration of URLs in seconds.'),
    ];

    // Delete URLs on use.
    $form['auto_login_url_delete_on_use'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete on use'),
      '#default_value' => $config->get('delete'),
      '#description' => $this->t('Auto delete URLs after use.'),
    ];

    // Token length.
    $form['auto_login_url_token_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token length'),
      '#required' => TRUE,
      '#default_value' => $config->get('token_length') != FALSE ? $config->get('token_length') : 64,
      '#description' => $this->t('Length of generated URL token.
      WARNING: Please understand the security implications of a short auto-login-url string before you change this value.
      It has to be between 8 and 64 digits.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('auto_login_url_expiration') < 1) {
      $form_state->setErrorByName('auto_login_url_expiration', $this->t('Expiration must be positive integer.'));
    }

    if ($form_state->getValue('auto_login_url_token_length') < 8 || $form_state->getValue('auto_login_url_token_length') > 64) {
      $form_state->setErrorByName('auto_login_url_token_length', $this->t('Token length has to be between 6 and 64 digits.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('auto_login_url.settings');
    $config->set('secret', $values['auto_login_url_secret'])->save();
    $config->set('expiration', $values['auto_login_url_expiration'])->save();
    $config->set('delete', $values['auto_login_url_delete_on_use'])->save();
    $config->set('token_length', $values['auto_login_url_token_length'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'auto_login_url.settings',
    ];
  }
}
