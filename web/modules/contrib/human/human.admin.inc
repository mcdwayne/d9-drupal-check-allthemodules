<?php

/**
 * @file
 * Human admin setting
 *
 * contains all forms and saving function for the human module.
 */

/**
 * Defines a settings form.
 */
function human_settings($form) {
  $form['human_form'] = array(
    '#type' => 'textarea',
    '#title' => t('Setup forms that will be checked by human module.'),
    '#default_value' => \Drupal::config('human.settings')->get('human_form'),
    '#description' => t('Enter the form ids for human module, seperate each form id with ",".'),
  );
  return system_settings_form($form);
}
