<?php

namespace Drupal\financial\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a SMS Sending Form.
 */
class SimpleInterestForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'simpleinterestform';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['principal'] = [
      '#type' => 'textfield',
      '#title' => 'Principal',
      '#size' => 20,
      '#maxlength' => 150,
      '#required' => TRUE,
    ];

    $form['years'] = [
      '#type' => 'textfield',
      '#title' => 'Number of Years',
      '#size' => 20,
      '#maxlength' => 5,
      '#required' => TRUE,
    ];

    $form['interest_rate'] = [
      '#type' => 'textfield',
      '#title' => 'Interest Rate Percentage',
      '#size' => 20,
      '#maxlength' => 150,
      '#required' => TRUE,
    ];

    $form['submit_button'] = [
      '#type' => 'submit',
      '#value' => t('Calculate'),
    // '#submit' => array('simple_interest_form_submit')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // $form_state['values']['principal'];.
    $principal = $form_state->getValue('principal');
    // $form_state['values']['years'];.
    $years = $form_state->getValue('years');
    // $form_state['values']['interest_rate'];.
    $interest = $form_state->getValue('interest_rate');

    $amount = ($principal * $interest * $years) / 100;
    $amount = number_format($amount, 2, '.', '');

    drupal_set_message("Simple Interest = " . $amount);

  }

}
