<?php

namespace Drupal\financial\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class SimpleCalculatorJSForm extends FormBase {

  /**
   *
   */
  public function getFormID() {
    return 'simple_calculator_js_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['loan_amount_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Principle or Sum [P]'),
      '#size' => 15,
      '#required' => TRUE,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['simple_rate_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rate % per Annum [R]'),
      '#size' => 15,
      '#required' => TRUE,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['years_to_pay_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time in years[T]'),
      '#size' => 15,
      '#required' => TRUE,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];

    $form['#executes_submit_callback'] = FALSE;
    $form['calculate_2'] = [
      '#type' => 'button',
      '#value' => $this->t('Calculate'),
    ];

    $form['result_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Simple Interest [S.I.]'),
      '#size' => 15,
      '#maxlength' => 64,
      '#attributes' => ['readonly' => ['readonly']],
    // '#description' => $this->t(''),.
    ];

    // Attach js and required js libraries.
    $form['#attached']['library'][] = 'system/jquery';
    $form['#attached']['library'][] = 'system/drupal';
    $form['#attached']['library'][] = 'financial/financial_js';
    // $form['#attached']['js'][] = array('data' => drupal_get_path('module', 'simple_calculator') . '/simple_calculator.js', 'type' => 'file');.
    return $form;
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
