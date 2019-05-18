<?php

namespace Drupal\cached_computed_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for the Cached Computed Field module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cached_computed_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cached_computed_field_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cached_computed_field.settings');

    $form['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#description' => $this->t('The number of items that will be processed in a single batch. Note that cron processing is time limited so multiple batches may be processed during a single cron run.'),
      '#default_value' => $config->get('batch_size'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['time_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Time limit'),
      '#description' => $this->t('The time, in seconds, that is allotted to processing expired items during a single cron run. This can be used to control server load.'),
      '#default_value' => $config->get('time_limit'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $batch_size = $form_state->getValue('batch_size');
    $time_limit = $form_state->getValue('time_limit');

    if (filter_var($batch_size, FILTER_VALIDATE_INT) === FALSE) {
      $form_state->setErrorByName('batch_size', $this->t('The batch size must be an integer value.'));
    }
    if (filter_var($time_limit, FILTER_VALIDATE_INT) === FALSE) {
      $form_state->setErrorByName('time_limit', $this->t('The time limit must be an integer value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('cached_computed_field.settings')
      ->set('batch_size', (int) $form_state->getValue('batch_size'))
      ->set('time_limit', (int) $form_state->getValue('time_limit'))
      ->save();
  }

}
