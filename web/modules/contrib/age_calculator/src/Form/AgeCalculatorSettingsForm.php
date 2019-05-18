<?php

namespace Drupal\age_calculator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddForm.
 *
 * @package Drupal\age_calculator\Form\AgeCalculatorSettingsForm
 */
class AgeCalculatorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'age_calculator.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'age_calculator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('age_calculator.settings');
    // Calling helper function to get output options.
    $options = $this->age_calculator_output_options();
    // Including helper functions inc file.
    module_load_include('inc', 'age_calculator', 'age_calculator.helper_functions');
    // Calling helper function to get already saved output options.
    $default_values = age_calculator_default_output_options();
    // Defining age calculator output form element.
    $form['age_calculator_output'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Select age calculator output format'),
      '#description' => $this->t('Please note that some values will be displayed with approximate values & may not be 100% true.'),
      '#options' => $options,
      '#default_value' => count($config->get('age_calculator_output')) > 0 ? $config->get('age_calculator_output') : $default_values,
      '#required' => TRUE,
      '#weight' => 0,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('age_calculator.settings')
        ->set('age_calculator_output', $form_state->getValue('age_calculator_output'))
        ->save();
  }

  /**
   * Helper function to get age calculator output options.
   *
   * @return array $options
   *   Returns the configuration options.
   */
  function age_calculator_output_options() {
    // Defining empty options array.
    $options = array();
    // Defining output formats inside options array.
    $options['age_calculator_years_months_days'] = $this->t('Y years M months D days');
    $options['age_calculator_months_days'] = $this->t('M months D days');
    $options['age_calculator_weeks_days'] = $this->t('W Weeks D days');
    $options['age_calculator_days'] = $this->t('D days');
    $options['age_calculator_hours'] = $this->t('H hours <b>(Approximate)</b>');
    $options['age_calculator_minutes'] = $this->t('M minutes <b>(Approximate)</b>');
    $options['age_calculator_seconds'] = $this->t('S seconds <b>(Approximate)</b>');
    // Returning options.
    return $options;
  }

}
