<?php

namespace Drupal\financial\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class CompoundCalculatorJSForm extends FormBase {

  /**
   *
   */
  public function getFormID() {
    return 'compound_calculator_js_form';
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
    $form['compound_rate_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interest Rate Percentage'),
      '#size' => 15,
      '#required' => TRUE,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['times_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of times Compounded'),
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
      '#title' => $this->t('Compound Interest [C.I.]'),
      '#size' => 15,
      '#maxlength' => 64,
      '#attributes' => ['readonly' => ['readonly']],
    // '#description' => $this->t(''),.
    ];

    // Attach js and required js libraries.
    $form['#attached']['library'][] = 'system/jquery';
    $form['#attached']['library'][] = 'system/drupal';
    $form['#attached']['library'][] = 'financial/financial_js';
    // $form['#attached']['js'][] = array('data' => drupal_get_path('module', 'compound_calculator') . '/compound_calculator.js', 'type' => 'file');.
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
