<?php

namespace Drupal\test_output_viewer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Test Output Viewer settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_output_viewer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['test_output_viewer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('test_output_viewer.settings');

    $form['output_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Relative path to test output directory'),
      '#default_value' => $config->get('output_path'),
      '#description' => $this->t('This directory is configured in phpunit.xml with BROWSERTEST_OUTPUT_DIRECTORY environment variable.'),
      '#required' => TRUE,
    ];

    $form['default_result'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default test result to display'),
      '#default_value' => $config->get('default_result'),
      '#options' => [
        'first' => $this->t('First'),
        'last' => $this->t('Last'),
      ],
      '#required' => TRUE,
    ];

    $form['auto_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto update'),
      '#description' => $this->t('Display new test results automatically'),
      '#default_value' => $config->get('auto_update'),
    ];

    $form['auto_update_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto update timeout'),
      '#default_value' => $config->get('auto_update_timeout'),
      '#field_suffix' => $this->t('seconds'),
      '#min' => 0.1,
      '#step' => 0.1,
      '#states' => [
        'visible' => [':input[name="auto_update"]' => ['checked' => TRUE]],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('test_output_viewer.settings')
      ->set('output_path', trim($form_state->getValue('output_path'), '/'))
      ->set('default_result', $form_state->getValue('default_result'))
      ->set('auto_update', $form_state->getValue('auto_update'))
      ->set('auto_update_timeout', $form_state->getValue('auto_update_timeout'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
