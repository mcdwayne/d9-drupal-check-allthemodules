<?php

/**
 * Batch import finished callback.
 */
function accessibility_tests_list_done($success, $results, $operations) {
  if ($success) {
    // Here we do something meaningful with the results.
    $message = count($results) . ' imported.';
    $message .= theme('item_list', $results);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $message = t('An error occurred while processing %error_operation with arguments: @arguments', array('%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)));
  }
  drupal_set_message($message);
}

/**
 * Creates a test entity from a quail test.
 */
function _accessibility_create_test_from_quail($quail_name, $test, &$context) {
  $language = language_default();
  $new_test = entity_create('accessibility_test', array(
      'language' => $language->id,
      'quail_name' => $quail_name,
      'name' => $test['title'],
      'severity' => (isset($test['severity'])) ? $test['severity'] : 'suggestion',
      'status' => 1
    ));
  $new_test->set('error_description', array('value'  => $test['description'],
                                         'format' => filter_default_format()
      ));
  $new_test->save();
  $context['message'] = t('Done importing @name', array('@name' => $new_test->name));
  $context['results'][] = $new_test->name;
}

/**
 * Form to filter accessibility tests.
 */
function accessibility_admin_filter_form($form, $form_state) {
  $form = array();

  $filter = (isset($_SESSION['accessibility_admin_filter'])) ? $_SESSION['accessibility_admin_filter'] : array();

  $form['filters'] = array(
    '#type' => 'fieldset'
    );

  $form['filters']['name'] = array(
    '#title' => t('Test name contains'),
    '#type' => 'textfield',
    '#default_value' => (isset($filter['name']) ? $filter['name'] : array())
  );

  $form['filters']['severity'] = array(
    '#title' => t('Severity level'),
    '#type' => 'checkboxes',
    '#options' => array(
      ACCESSIBILITY_TEST_SEVERE => t('Severe'),
      ACCESSIBILITY_TEST_MODERATE => t('Moderate'),
      ACCESSIBILITY_TEST_SUGGESTION => t('Suggestion'),
    ),
    '#default_value' => (isset($filter['severity']) ? $filter['severity'] : array())
  );

  $form['filters']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Search'),
    );

  $form['filters']['reset'] = array(
    '#type' => 'submit',
    '#value' => t('Reset'),
    );

  return $form;
}

/**
 * Form submit callback for filter form.
 */
function accessibility_admin_filter_form_submit($form, $form_state) {
  $_SESSION['accessibility_admin_filter'] = $form_state['values'];
  if ($form_state['clicked_button']['#value'] == t('Reset')) {
    $_SESSION['accessibility_admin_filter'] = array();
  }
}