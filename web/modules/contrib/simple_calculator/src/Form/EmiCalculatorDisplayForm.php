<?php

/**
 * @file
 * Contains \Drupal\simple_calculator\Form\EmiCalculatorDisplayForm.
 */

namespace Drupal\simple_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

class EmiCalculatorDisplayForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'emi_calculator_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    // attach js and required js libraries
    $form['emicalculator'] = array(
      '#type' => 'table',
      '#caption' => $this->t('EMI Calculator '),
      '#header' => array($this->t('Description'), $this->t('Amount Values')),
    );
    $form['emicalculator'][1]['name'] = array(
      '#markup' => $this->t('Principal Amount(in Rs.) :'),
    );
    $form['emicalculator'][1]['emi_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('emi_value'),
      '#title_display' => 'invisible',
    );
    $form['emicalculator'][2]['name'] = array(
      '#markup' => $this->t('Interest Rate :'),
    );
    $form['emicalculator'][2]['emi_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('emi_value'),
      '#title_display' => 'invisible',
      '#attributes' => array('id' => array('interest_rate')),
    );
    $form['emicalculator'][3]['name'] = array(
      '#markup' => $this->t('Period (Months) :'),
    );
    $form['emicalculator'][3]['emi_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('emi_value'),
      '#title_display' => 'invisible',
    );
    $form['#executes_submit_callback'] = FALSE;
    $form['calculate'] = array(
      '#type' => 'button',
      '#value' => $this->t('Calculate'),
    );
    $form['clear'] = array(
      '#type' => 'button',
      '#value' => $this->t('clear'),
    );
    $form['emicalculator_Values'] = array(
      '#type' => 'table',
      '#caption' => $this->t('Loan Details'),
    );
    $form['emicalculator_Values'][1]['name'] = array(
      '#markup' => $this->t('EMI (Rs.):'),
    );
    $form['emicalculator_Values'][1]['emi_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('emi_value'),
      '#title_display' => 'invisible',
      '#attributes' => array('readonly' => array('readonly')),
    );
    $form['emicalculator_Values'][2]['name'] = array(
      '#markup' => $this->t('Interest Amount (Rs.):'),
    );
    $form['emicalculator_Values'][2]['emi_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('emi_value'),
      '#title_display' => 'invisible',
      '#attributes' => array('readonly' => array('readonly')),
    );
    $form['emicalculator_Values'][3]['name'] = array(
      '#markup' => $this->t('Total Amount(Rs.) :'),
    );
    $form['emicalculator_Values'][3]['emi_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('emi_value'),
      '#title_display' => 'invisible',
      '#attributes' => array('readonly' => array('readonly')),
    );
    $form['print'] = array(
      '#type' => 'button',
      '#value' => $this->t('Print'),
    );
    $form['print_table'] = array(
     '#markup' => '<div id="table-box"></div>',
    );
    $form['#attached']['library'][] = 'system/jquery';
    $form['#attached']['library'][] = 'system/drupal';
    $form['#attached']['library'][] = 'simple_calculator/emi_calculator';
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
