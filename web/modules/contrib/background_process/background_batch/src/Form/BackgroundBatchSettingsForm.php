<?php

namespace Drupal\background_batch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Default controller for the background_batch module.
 */
class BackgroundBatchSettingsForm extends ConfigFormBase {

  /**
   * Implements to Get Form ID.
   */
  public function getFormId() {
    return 'background_batch_settings_form';
  }

  /**
   * Implements When Submit Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('background_batch.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Implements to Get Editable Config Names From Config Files.
   */
  protected function getEditableConfigNames() {
    return ['background_batch.settings'];
  }

  /**
   * Implements to Build the Batch Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['background_batch_delay'] = [
      '#type' => 'textfield',
      '#default_value' => \Drupal::config('background_batch.settings')->get('background_batch_delay'),
      '#title' => 'Delay',
      '#description' => $this->t('Time in microseconds for progress refresh'),
    ];
    $form['background_batch_process_lifespan'] = [
      '#type' => 'textfield',
      '#default_value' => \Drupal::config('background_batch.settings')->get('background_batch_process_lifespan'),
      '#title' => 'Process lifespan',
      '#description' => $this->t('Time in milliseconds for progress lifespan'),
    ];
    $form['background_batch_show_eta'] = [
      '#type' => 'checkbox',
      '#default_value' => \Drupal::config('background_batch.settings')->get('background_batch_show_eta'),
      '#title' => 'Show ETA of batch process',
      '#description' => $this->t('Whether ETA (estimated time of arrival) information should be shown'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
