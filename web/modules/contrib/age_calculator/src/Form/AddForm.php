<?php

namespace Drupal\age_calculator\Form;

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
 * Class AddForm.
 *
 * @package Drupal\age_calculator\Form\AddForm
 */
class AddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'age_calc_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = array();

    // Birthdate Field definition.
    $form['birthdate'] = array(
      '#title' => $this->t('Date of birth'),
      '#type' => 'date',
      '#weight' => 1,
      '#default_value' => ''
    );

    // Age at the date.
    $form['age_on_date'] = array(
      '#title' => $this->t('Age on date'),
      '#type' => 'date',
      '#weight' => 2,
      '#default_value' => '',
    );

    // Submit button definition.
    $form['calculateAge'] = array(
      '#type' => 'button',
      '#value' => $this->t('Calculate'),
      '#weight' => 3,
      '#ajax' => array(
        // Function to call when event on form element triggered.
        'callback' => '::calculateAge',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => 'Calculating Age..',
        ),
      ),
    );

    // Results section markup.
    $form['calculated_age'] = array(
      '#type' => 'markup',
      '#weight' => 4,
      '#prefix' => '<div id="age_calculator_calculated_age">',
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

  public function calculateAge(array &$form, FormStateInterface $form_state) {
    $output = '';
    // If birthdate is not empty.
    if (!empty($form_state->getValue('birthdate'))) {
      $birthdate_array = \explode('-', $form_state->getValue('birthdate'));
      $age_on_date_array = \explode('-', $form_state->getValue('age_on_date'));
      // Formatting user input.
      $birthdate = $birthdate_array[2] . '-' . $birthdate_array[1] . '-' . $birthdate_array[0];
      $age_on_date = $age_on_date_array[2] . '-' . $age_on_date_array[1] . '-' . $age_on_date_array[0];
      // Convert dates to timestamps.
      $birthdate_timestamp = strtotime($birthdate);
      $age_on_date_timestamp = strtotime($age_on_date);
      // Check if birthdate greater than age on time.
      if ($birthdate_timestamp <= $age_on_date_timestamp) {
        // Object declaration.
        $birthdate_datetime = new \DateTime($birthdate);
        $age_on_date_datetime = new \DateTime($age_on_date);
      
        // Including helper functions inc file.
        module_load_include('inc', 'age_calculator', 'age_calculator.helper_functions');
        // Getting output.
        $output = age_calculator_get_results($birthdate_datetime, $age_on_date_datetime);
      }
      else {
        $output = $this->t('ERROR: Age on date should not be lesser than date of birth.');
      }
     // debug($form_state->getValue('birthdate'), $label = 'date', $print_r = TRUE);
    }
    else {
      $output = $this->t('ERROR: Date of Birth Field can not be empty.');
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#age_calculator_calculated_age', $output));
    return $response;
  }

}
