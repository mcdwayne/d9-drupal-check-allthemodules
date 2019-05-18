<?php

/**
 * @file
 * Implements Ass Setting Form.
 */

/**
 * Implements definition for settings page.
 */
function background_process_ass_settings_form() {
  $form = [];
  $form['background_process_ass_max_age'] = [
    '#type' => 'textfield',
    '#title' => t('Max age'),
    '#description' => t('Time in seconds to wait before considering a process dead.'),
    '#default_value' => \Drupal::config('background_process_ass.settings')->get('background_process_ass_max_age'),
  ];
  return system_settings_form($form);
}
