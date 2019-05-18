<?php

namespace Drupal\mortgage_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * MortgageCalculatorJSForm.
 *
 * @internal
 */
class MortgageCalculatorJSForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mortgage_calculator_js_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['loan_amount_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price of Home'),
      '#size' => 10,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['mortgage_rate_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mortgage Rate'),
      '#size' => 10,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['years_to_pay_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Years to Pay'),
      '#size' => 10,
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
      '#title' => $this->t('Monthly Payment'),
      '#size' => 10,
      '#maxlength' => 64,
      '#attributes' => ['readonly' => ['readonly']],
    // '#description' => $this->t(''),.
    ];

    // Attach js and required js libraries.
    $form['#attached']['library'][] = 'mortgage_calculator/mortgage_calculator_js';

    return $form;
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

  }

}
