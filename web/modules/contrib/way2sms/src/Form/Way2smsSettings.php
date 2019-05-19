<?php

namespace Drupal\way2sms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class Way2smsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'way2sms_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['way2sms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('way2sms.settings');
    $form['way2sms_senders_phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Sender's contact number"),
      '#description' => $this->t('Enter the registered contact number at way2sms. For example: 7722800915. Note: Do not use +91, 0.'),
      '#default_value' => $config->get('way2sms_senders_phone_number'),
      '#size' => 10,
      '#maxlength' => 10,
      '#required' => TRUE,
    ];
    $form['way2sms_senders_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Way2SMS account password'),
      '#description' => $this->t('Enter login password as your way2sms password. Note: You need to have an account to way2sms.'),
      '#size' => 10,
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['way2sms_senders_phone_number'] && $values['way2sms_senders_password']) {
      if (!_way2sms_login($values['way2sms_senders_phone_number'], $values['way2sms_senders_password'])) {
        drupal_set_message('way2sms_senders_phone_number', t('Please provide valid Credentials'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('way2sms.settings')
      ->set('way2sms_senders_phone_number', $values['way2sms_senders_phone_number'])
      ->set('way2sms_senders_password', $values['way2sms_senders_password'])
      ->save();
  }

}
