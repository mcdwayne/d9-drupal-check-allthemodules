<?php

namespace Drupal\sula_calculator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\Xss;

/**
 * Configure site information settings for this site.
 */
class SulaCalculatorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sula_calculator_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sula_calculator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sula_config = $this->config('sula_calculator.settings');

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('SULA Calculator settings'),
      '#description' => $this->t('This form will allow you to set your standard values for your institution.'),
    ];

    $form['term'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Institution specific settings'),
    ];

    $form['term']['year_length_clock'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Academic Year Length for Clock calculator'),
      '#default_value' => $sula_config->get('acad_year_length_clock'),
      '#description' => $this->t('Enter the length (in weeks) for your institutions academic year.'),
    ];

    $form['term']['year_length_credit'] = [
      '#type' => 'number',
      '#title' => $this->t('Default Academic Year Length for Credit calculator'),
      '#default_value' => $sula_config->get('acad_year_length_credit'),
      '#description' => $this->t('Enter the length (in weeks) for your institutions academic year.'),
    ];

    $form['term']['disclaimer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disclaimer'),
      '#default_value' => $sula_config->get('disclaimer'),
      '#description' => $this->t('To appear just underneath the block title.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Runs the basic PHP data validation commands on the input data.
   *
   * @param string $data
   *   The input data.
   *
   * @return string
   *   The sanitized data.
   */
  public function sanitizeInput($data) {
    $string = (string) $data;
    $string = trim($string);
    $string = stripslashes($string);
    $string = Xss::filter($string);
    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('year_length_clock') < 1) {
      $form_state->setErrorByName('year_length_clock', $this->t('Default Academic year length cannot be less than 1.'));
    }
    if ($form_state->getValue('year_length_credit') < 1) {
      $form_state->setErrorByName('year_length_credit', $this->t('Default Academic year length cannot be less than 1.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('sula_calculator.settings')
      ->set('acad_year_length_clock', $form_state->getValue('year_length_clock'))
      ->set('acad_year_length_credit', $form_state->getValue('year_length_credit'))
      ->set('disclaimer', $this->sanitizeInput($form_state->getValue('disclaimer')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
