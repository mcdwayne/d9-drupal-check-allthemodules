<?php

namespace Drupal\onepilot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures 1pilot module settings.
 */
class OnePilotConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onepilot_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'onepilot.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('onepilot.settings');

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('1Pilot Private Key'),
      '#default_value' => $config->get('private_key'),
    ];

    $form['skip_timestamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('1Pilot Skip Timestamp Check'),
      '#default_value' => $config->get('skip_timestamp'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('onepilot.settings')
      ->set('private_key', $values['private_key'])
      ->save();

    $this->config('onepilot.settings')
      ->set('skip_timestamp', $values['skip_timestamp'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
