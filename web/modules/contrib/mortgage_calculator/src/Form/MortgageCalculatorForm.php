<?php

namespace Drupal\mortgage_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * MortgageCalculatorForm.
 *
 * @internal
 */
class MortgageCalculatorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mortgage_calculator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $request = $this->getRequest();
    $session = $request->getSession();

    $form['mortgage_calculator_loan_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price of Home'),
      '#default_value' => $session->get('mortgage_calculator_loan_amount', ''),
      '#size' => 10,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['mortgage_calculator_mortgage_rate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mortgage Rate'),
      '#default_value' => $session->get('mortgage_calculator_mortgage_rate', ''),
      '#size' => 10,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['mortgage_calculator_years_to_pay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Years to Pay'),
      '#default_value' => $session->get('mortgage_calculator_years_to_pay', ''),
      '#size' => 10,
      '#maxlength' => 64,
    // '#description' => $this->t(''),.
    ];
    $form['mortgage_calculator_desired_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Desired table display'),
      '#default_value' => $session->get('mortgage_calculator_desired_display', ''),
      '#options' => ['monthly' => $this->t('monthly'), 'yearly' => $this->t('yearly')],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('mortgage_calculator_years_to_pay') == '' || $form_state->getValue('mortgage_calculator_years_to_pay') <= 0) {
      $form_state->setErrorByName('mortgage_calculator_years_to_pay', $this->t('Please enter a value of years to pay.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $request = $this->getRequest();
    $session = $request->getSession();
    $session->set('mortgage_calculator_loan_amount', $form_state->getValue('mortgage_calculator_loan_amount'));
    $session->set('mortgage_calculator_mortgage_rate', $form_state->getValue('mortgage_calculator_mortgage_rate'));
    $session->set('mortgage_calculator_years_to_pay', $form_state->getValue('mortgage_calculator_years_to_pay'));
    $session->set('mortgage_calculator_desired_display', $form_state->getValue('mortgage_calculator_desired_display'));

    // $form_state->setRedirect('mortgage_calculator.page');.
  }

}
