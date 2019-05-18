<?php

namespace Drupal\messagebird\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\messagebird\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'messagebird_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'messagebird.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('messagebird.settings');

    $form['messagebird_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api.key'),
    );

    $form['messagebird_default_originator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default originator'),
      '#required' => TRUE,
      '#maxlength' => 11,
      '#default_value' => $config->get('default.originator'),
    );

    $form['messagebird_credit_balance_warning'] = array(
      '#type' => 'number',
      '#title' => $this->t('Credit balance warning'),
      '#description' => $this->t('Display a warning on the status report if the credit balance is below this amount. Set to -1 to disable the warning.'),
      '#attributes' => array(
        'min' => -1,
        'step' => 1,
        'value' => $config->get('credit.balance.warning'),
      ),
    );

    $form['messagebird_debug_mode'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#description' => $this->t('Debug mode displays the message data every time a message is sent.'),
      '#default_value' => $config->get('debug.mode'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('messagebird.settings')
      ->set('api.key', $form_state->getValue('messagebird_api_key'))
      ->set('default.originator', $form_state->getValue('messagebird_default_originator'))
      ->set('credit.balance.warning', $form_state->getValue('messagebird_credit_balance_warning'))
      ->set('debug.mode', $form_state->getValue('messagebird_debug_mode'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
