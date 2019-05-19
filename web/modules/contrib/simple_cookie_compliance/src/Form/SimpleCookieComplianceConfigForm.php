<?php

namespace Drupal\simple_cookie_compliance\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides cookie compliance configuration form.
 */
class SimpleCookieComplianceConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_cookie_compliance_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_cookie_compliance.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('simple_cookie_compliance.settings');

    $form['expires'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie expiration time'),
      '#default_value' => $config->get('expires'),
      '#required' => TRUE,
      '#description' => $this->t('The time it will take to expire the cookie in seconds. Default value is equal to three months (7776000 seconds). Zero "0" means that the cookie never expires.'),
    ];

    $form['no_script'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display message'),
      '#default_value' => $config->get('no_script'),
      '#description' => $this->t('A clever description'),
    ];

    $form['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('message.value'),
      '#required' => TRUE,
      '#format' => !empty($config->get('message.format')) ? $config->get('message.format') : filter_fallback_format(),
    ];

    $form['agree_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agree button text'),
      '#default_value' => $config->get('agree_button'),
      '#required' => TRUE,
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
    \Drupal::service('config.factory')->getEditable('simple_cookie_compliance.settings')
      ->set('message', $form_state->getValue('message'))
      ->set('expires', $form_state->getValue('expires'))
      ->set('no_script', $form_state->getValue('no_script'))
      ->set('agree_button', $form_state->getValue('agree_button'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
