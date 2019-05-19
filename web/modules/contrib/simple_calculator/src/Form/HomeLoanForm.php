<?php

/**
 * @file
 * Contains \Drupal\simple_calculator\Form\HomeLoanForm.
 */

namespace Drupal\simple_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

class HomeLoanForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'home_loan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $form['loan_amount'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Loan Amount (Rs.):'),
      '#size' => 10,
      '#maxlength' => 64,
    );
    $form['loan_interest'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Interest Rate:'),
      '#size' => 10,
      '#maxlength' => 64,
    );
    $form['loan_length'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Loan Length in Months:'),
      '#size' => 10,
      '#maxlength' => 64,
    );
    $form['#executes_submit_callback'] = FALSE;
    $form['calculate_2'] = array(
      '#type' => 'button',
      '#value' => $this->t('Calculate'),
    );
    $form['result_2'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Monthly Payment (Rs.)'),
      '#size' => 10,
      '#maxlength' => 64,
      '#attributes' => array('readonly' => array('readonly')),
    );
    // attach js and required js libraries
    $form['#attached']['library'][] = 'system/jquery';
    $form['#attached']['library'][] = 'system/drupal';
    $form['#attached']['library'][] = 'simple_calculator/homeloan_calculator';
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
