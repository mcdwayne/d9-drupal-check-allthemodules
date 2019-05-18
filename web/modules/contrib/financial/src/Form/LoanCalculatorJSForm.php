<?php

namespace Drupal\financial\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class LoanCalculatorJSForm extends FormBase {

  /**
   *
   */
  public function getFormID() {
    return 'loan_calculator_js_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['loan_amount_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Principle'),
      '#size' => 15,
      '#required' => TRUE,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['years_to_pay_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of Years'),
      '#size' => 15,
      '#required' => TRUE,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['loan_rate_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interest Rate Percentage'),
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

    $form['result_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Monthly Payment:'),
      '#size' => 15,
      '#maxlength' => 64,
      '#attributes' => ['readonly' => ['readonly']],
    // '#description' => $this->t(''),.
    ];

    // Attach js and required js libraries.
    $form['#attached']['library'][] = 'system/jquery';
    $form['#attached']['library'][] = 'system/drupal';
    $form['#attached']['library'][] = 'financial/financial_js';
    // $form['#attached']['js'][] = array('data' => drupal_get_path('module', 'loan_calculator') . '/loan_calculator.js', 'type' => 'file');.
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
