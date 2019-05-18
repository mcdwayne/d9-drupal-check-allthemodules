<?php

namespace Drupal\timeout_notification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for administering timeout notification settings.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'timeout_notification_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('timeout_notification.settings');
    $form['to_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Session Timeout Notification Settings'),
      '#open' => TRUE,
    );
    $form['to_settings']['time_till_expire'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seconds Before Session Expiration to Notify User'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('time_till_expire'),
      '#description' => $this->t('(Note: Your drupal sessions are set to expire after ' . ini_get("session.gc_maxlifetime") . ' seconds of inactivity.)'),
    ];
    $form['#attached']['library'][] = 'timeout_notification/timeout_notification.form';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('timeout_notification.settings')
      ->set('time_till_expire', $form_state->getValue('time_till_expire'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['timeout_notification.settings'];
  }

}
