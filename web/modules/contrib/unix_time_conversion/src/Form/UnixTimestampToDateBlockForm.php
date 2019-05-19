<?php

/**
 * @file
 * Contains Drupal\unix_time_conversion\Form\UnixTimeConversionForm
 */

namespace Drupal\unix_time_conversion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form;

/**
 * Class UnixTimeConversionForm.
 *
 * @package Drupal\unix_time_conversion\Form\UnixTimeConversionForm
 */
class UnixTimestampToDateBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unix_timestamp_to_date_calculate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('unix_time_conversion.settings');
    $form = array();
    $timestamp_title = $config->get('unix_time_conversion_timestamp_field_title');
    $timestamp_description = $config->get('unix_time_conversion_timestamp_field_description');
    $form['timestamp'] = array(
      '#title' => t('@timestamp_title', array('@timestamp_title' => $timestamp_title)),
      '#type' => 'textfield',
      '#size' => 20,
      '#weight' => 1,
      '#description' => t('@timestamp_desc', array('@timestamp_desc' => $timestamp_description)),
    );

    // Calculate submit button.
    $form['calculateDate'] = array(
      '#value' => 'Calculate',
      '#type' => 'button',
      '#ajax' => array(
        // Function to call when event on form element triggered.
        'callback' => '::calculateDate',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => 'Calculating Date..',
        ),
      ),
      '#weight' => 2,
    );

    // Calculated date inside markup.
    $form['calculated_date'] = array(
      '#type' => 'markup',
      '#weight' => 4,
      '#prefix' => '<div id="unix_time_conversion_calculated_date">',
      '#suffix' => '</div>',
    );
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

  public function calculateDate(&$form, FormStateInterface $form_state) {
    $output = '';
    // Check if the form is submitted then compute the date accordingly.
    if (!empty($form_state->getValue('timestamp'))) {
      // Include the helper functions file.
      module_load_include('inc', 'unix_time_conversion', 'unix_time_conversion.helper_functions');
      // Check if the timestamp is numeric.
      if (is_numeric($form_state->getValue('timestamp'))) {
        // Timestamp variable decleration.
        $timestamp = $form_state->getValue('timestamp');
        // Theme the markup output.
        $output = unix_time_conversion_get_date_from_timestamp($timestamp);
      }
      else {
        $output = t('ERROR: Invalid timestamp.');
      }
    }
    else {
      $output = t('ERROR: Enter the Timestamp.');
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#unix_time_conversion_calculated_date', $output));
    return $response;
  }

}
