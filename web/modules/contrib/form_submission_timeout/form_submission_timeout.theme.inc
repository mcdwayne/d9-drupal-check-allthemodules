<?php

/**
 * @file
 * Administration pages for image settings.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Prepares variables for admin config templates.
 *
 * Default template: form-submission-timeout-config-form.html.twig
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: An associative array containing the fieldset.
 */
function template_preprocess_form_submission_timeout_config_form(&$variables) {
  $fieldset = $variables['form'];
  $children = Element::children($fieldset);

  // Remove the input section from the form.
  array_pop($children);

  // Table header.
  $header = array(
    t('Form ID'),
    t('Show Countdown Timer'),
    t('Timeout period (seconds)'),
    t('Timeout message'),
    t('Operation')
  );

  // Table rows.
  foreach ($children as $child) {

    $row = array();
    $row[] = drupal_render($fieldset[$child]['sub_out_form_id_display']);
    $row[] = drupal_render($fieldset[$child]['sub_out_show_timer']);
    $row[] = drupal_render($fieldset[$child]['sub_out_timeout_period']);
    $row[] = drupal_render($fieldset[$child]['sub_out_timeout_message']);
    $row[] = drupal_render($fieldset[$child]['sub_out_remove']);
    $rows[] = $row;
  }

  // Additional row for new form_id input.
  $rows[] = array(
    drupal_render($fieldset['new_form_entry']['sub_out_new_form_id']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_show_timer']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_timeout_period']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_timeout_message']),
    ''
  );

  $variables['table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'class' => array('form-submission-timeout-table')
    ),
  );
}

function template_preprocess_form_submission_timeout_timed_config_form(&$variables){
  $fieldset = $variables['form'];
  $children = Element::children($fieldset);

  // Remove the input section from the form.
  array_pop($children);

  // Table header.
  $header = array(
    'form_id' => array(
      'data' => t('Form ID'),
      'class' => array('form-id'),
    ),
    'frequency' => array(
      'data' => t('Frequency'),
      'class' => array('frequency'),
    ),
    'dateFrom' => array(
      'data' => t('Date from'),
      'class' => array('date-from'),
    ),
    'timeStart' => array(
      'data' => t('Time start'),
      'class' => array('time-start'),
    ),
    'dateTo' => array(
      'data' => t('Date to'),
      'class' => array('date-to'),
    ),
    'timeStop' => array(
      'data' => t('Time stop'),
      'class' => array('time-stop'),
    ),
    'timeOutMessage' => array(
      'data' => t('Timeout message'),
      'class' => array('time-out-message '),
    ),
    'operation' => array(
      'data' => t('Operation'),
      'class' => array('operation'),
    )
  );

  // Table rows.
  foreach ($children as $child) {
    $row = array();
    $row[] = drupal_render($fieldset[$child]['sub_out_stop_form_id_display']);
    $row[] = drupal_render($fieldset[$child]['sub_out_timeout_frequency']);
    $row[] = drupal_render($fieldset[$child]['sub_out_start_date']);
    $row[] = drupal_render($fieldset[$child]['sub_out_start_timeout_period']);
    $row[] = drupal_render($fieldset[$child]['sub_out_stop_date']);
    $row[] = drupal_render($fieldset[$child]['sub_out_stop_timeout_period']);
    $row[] = drupal_render($fieldset[$child]['sub_out_stop_timeout_message']);
    $row[] = drupal_render($fieldset[$child]['sub_out_stop_remove']);
    $rows[] = $row;
  }

  // Additional row for new form_id input.
  $rows[] = array(
    drupal_render($fieldset['new_form_entry']['sub_out_new_stop_form_id']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_timeout_frequency']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_start_date']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_start_timeout_period']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_stop_date']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_stop_timeout_period']),
    drupal_render($fieldset['new_form_entry']['sub_out_new_stop_timeout_message']),
    ''
  );

  $variables['table'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'class' => array('form-submission-timeout-table timed')
    ),
  );
}