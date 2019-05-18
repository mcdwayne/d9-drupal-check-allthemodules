<?php

/**
 * Page callback for settings.
 */
function yplog_settings() {
  $form['yplog_font'] = array(
    '#type' => 'textfield',
    '#title' => t('Graph font'),
    '#description' => t('Enter the path to a font file on the server file system.'),
    '#default_value' => \Drupal::config('yplog.settings')->get('yplog_font'),
  );
  return system_settings_form($form);
}
