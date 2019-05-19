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
class DateToUnixTimestampBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'date_to_unix_timestamp_calculate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('unix_time_conversion.settings');
    $form = array();
    $form['#tree'] = TRUE;
    // Include the helper functions file.
    module_load_include('inc', 'unix_time_conversion', 'unix_time_conversion.helper_functions');

    // Date Input.
    $date_title = $config->get('unix_time_conversion_date_field_title');
    $form['date'] = array(
      '#title' => t('@date_title', array('@date_title' => $date_title)),
      '#type' => 'date',
      '#weight' => 1,
    );
    // Time Input.
    $time_title = $config->get('unix_time_conversion_time_field_title');
    $form['time'] = array(
      '#title' => t('@time_title', array('@time_title' => $time_title)),
      '#type' => 'time_element',
      '#weight' => 2,
    );
    // Calculate button.
    $form['calculateTime'] = array(
      '#value' => 'Calculate',
      '#type' => 'button',
      '#ajax' => array(
        // Function to call when event on form element triggered.
        'callback' => '::calculateTime',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => 'Calculating Time..',
        ),
      ),
      '#weight' => 3,
    );
    // Calculated timestamp inside markup.
    $form['calculated_timestamp'] = array(
      '#type' => 'markup',
      '#weight' => 4,
      '#prefix' => '<div id="unix_time_conversion_calculated_timestamp">',
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

  public function calculateTime(&$form, FormStateInterface $form_state) {
    $output = '';
    // Include the helper functions file.
    module_load_include('inc', 'unix_time_conversion', 'unix_time_conversion.helper_functions');
    // Check if the form is submitted then compute the timestamp accordingly.
    if (!empty($form_state->getValue('date'))) {
      // User submited date and time.
      $date = $form_state->getValue('date');
      //$date = explode('-',$date_value);
      $time = $form_state->getValue('time');
      // Theme the markup output.
      $output = unix_time_conversion_get_timestamp_from_date_and_time($date, $time);
    }
    else {
      $output = t('ERROR: Date Field can not be empty');
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#unix_time_conversion_calculated_timestamp', $output));
    return $response;
  }

}
