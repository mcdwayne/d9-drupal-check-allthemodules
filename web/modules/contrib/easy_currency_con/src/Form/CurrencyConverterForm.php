<?php

/**
 * @file
 * Contains \Drupal\easy_currency_con\Form\CurrencyConverterForm.
 */

namespace Drupal\easy_currency_con\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Currency Converter Form.
 */
class CurrencyConverterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'easy_currency_con_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_path;
    $module_path = drupal_get_path('module', 'easy_currency_con');
    $img_path = $base_path . $module_path . '/images/conversion_icon.png';

    $form['amount'] = array(
      '#type' => 'textfield',
      '#attributes' => array('class' => array('easy-currency-con-amount')),
      '#size' => 30,
      '#default_value' => 1,
    );
    $form['from_input'] = array(
      '#type' => 'textfield',
      '#attributes' => array('class' => array('easy-currency-con-from-input')),
      '#maxlength' => 10,
      '#size' => 30,
      '#autocomplete_route_name' => 'easy_currency_con.country_autocomplete',
    );
    $form['inverse_input'] = array(
      '#type' => 'image_button',
      '#name' => 'reverse input',
      '#src' => $img_path,
      '#ajax' => array(
        'callback' => '::displayConversion',
        'event' => 'click',
        'progress' => array(
          'type' => 'none',
          'message' => t('Processing...'),
        ),
      ),
    );
    $form['to_input'] = array(
      '#type' => 'textfield',
      '#attributes' => array('class' => array('easy-currency-con-to-input')),
      '#maxlength' => 10,
      '#size' => 30,
      '#autocomplete_route_name' => 'easy_currency_con.country_autocomplete',
    );
    $form['rate'] = array(
      '#type' => 'textfield',
      '#attributes' => array('class' => array('easy-currency-con-rate'), 'disabled' => 'disabled'),
      '#size' => 30,
    );
    return $form;
  }

  /**
   * Submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * AJAX Callback.
   */
  public function displayConversion($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('', 'inverseInput'));
    return $response;
  }

}
