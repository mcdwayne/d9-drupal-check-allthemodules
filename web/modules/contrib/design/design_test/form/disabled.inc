<?php

/**
 * @file
 * Fieldset design test.
 */

/**
 * Disabled form elements.
 */
function design_test_form_disabled($form, &$form_state) {
  // Single system form elements.
  foreach (array('textfield', 'textarea', 'radio', 'checkbox', 'weight', 'date', 'file') as $type) {
    $form[$type] = array(
      '#type' => $type,
      '#title' => drupal_ucfirst(strtr($type, '_', ' ')),
      '#disabled' => TRUE,
      // Required for 'radio'.
      '#return_value' => 1,
    );
  }

  // Multiple system form elements.
  foreach (array('radios', 'checkboxes', 'select') as $type) {
    $form[$type] = array(
      '#type' => $type,
      '#title' => drupal_ucfirst(strtr($type, '_', ' ')),
      '#options' => array('one' => 'one', 'two' => 'two'),
      '#disabled' => TRUE,
    );
  }

  // Expanded system elements.
  $form['password'] = array(
    '#type' => 'password',
    '#title' => 'Password',
    '#disabled' => TRUE,
  );
  $form['password_confirm'] = array(
    '#type' => 'password_confirm',
    '#title' => 'Password confirm',
    '#disabled' => TRUE,
  );

  // Module elements.
  $form['managed_file'] = array(
    '#type' => 'managed_file',
    '#title' => 'Managed file',
    '#disabled' => TRUE,
  );
  $form['text_format'] = array(
    '#type' => 'text_format',
    '#title' => 'Text format',
    '#default_value' => 'Some value',
    '#disabled' => TRUE,
  );

  // Form actions.
  $form['actions'] = array('#type' => 'actions');
  foreach (array('image_button', 'button', 'submit') as $type) {
    $form['actions'][$type] = array(
      '#type' => $type,
      '#value' => drupal_ucfirst(strtr($type, '_', ' ')),
      '#disabled' => TRUE,
    );
  }

  return $form;
}

