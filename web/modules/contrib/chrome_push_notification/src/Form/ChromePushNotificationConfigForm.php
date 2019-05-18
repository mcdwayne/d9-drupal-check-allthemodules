<?php

namespace Drupal\chrome_push_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ChromePushNotificationConfigForm.
 *
 * @package Drupal\chrome_push_notification\Form
 */
class ChromePushNotificationConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'chrome_push_notification.gpn',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chrome_push_notification_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Get config.
    $config_gpn = $this->config('chrome_push_notification.gpn');

    // Google Chrome Messaging.
    $form['gpn'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Chrome Push Notification'),
      '#description' => $this->t('Enter your Google Chrome Push Notification details.'),
    ];

    $form['gpn']['gpn_api_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Chrome Push Notification API ID'),
      '#description' => $this->t('Enter the API ID for your Google Chrome Push Notification'),
      '#default_value' => $config_gpn->get('chrome_push_notification_api_id'),
    ];

    $form['gpn']['gpn_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Chrome Push Notification API Key'),
      '#description' => $this->t('Enter the API key for your Google Chrome Push Notification'),
      '#default_value' => $config_gpn->get('chrome_push_notification_api_key'),
      '#maxlength' => 512,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Both the field is should be manatory.
    $gpnApiId = strtolower($form_state->getValue('gpn_api_id'));
    if (empty($gpnApiId)) {
      $form_state->setErrorByName('gpn_api_id', $this->t('Please enter a google api id.'));
    }

    $gpnApiKey = strtolower($form_state->getValue('gpn_api_key'));
    if (empty($gpnApiKey)) {
      $form_state->setErrorByName('gpn_api_key', $this->t('Please enter a google api key.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Store GCM config.
    $config_gpn = $this->config('chrome_push_notification.gpn');
    $config_gpn->set('chrome_push_notification_api_id', $form_state->getValue('gpn_api_id'));
    $config_gpn->set('chrome_push_notification_api_key', $form_state->getValue('gpn_api_key'));
    $config_gpn->save();
    drupal_set_message($this->t('Chrome Push Notification settings is saved.'));
  }

}
