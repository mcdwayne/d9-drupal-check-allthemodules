<?php

/**
 * @file
 * Implements Admin Pages.
 */

/**
 * Implements definition for settings page.
 */
function background_process_settings_form() {
  $form = [];
  $form['background_process_service_timeout'] = [
    '#type' => 'textfield',
    '#title' => t('Service timeout'),
    '#description' => t('Timeout for service call in seconds (0 = disabled)'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_service_timeout'),
  ];

  $form['background_process_connection_timeout'] = [
    '#type' => 'textfield',
    '#title' => t('Connection timeout'),
    '#description' => t('Timeout for connection in seconds'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_connection_timeout'),
  ];

  $form['background_process_stream_timeout'] = [
    '#type' => 'textfield',
    '#title' => t('Stream timeout'),
    '#description' => t('Timeout for stream in seconds'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_stream_timeout'),
  ];

  $form['background_process_redispatch_threshold'] = [
    '#type' => 'textfield',
    '#title' => t('Redispatch threshold (for locked processes)'),
    '#description' => t('Seconds to wait before redispatching processes that never started.'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_redispatch_threshold'),
  ];

  $form['background_process_cleanup_age'] = [
    '#type' => 'textfield',
    '#title' => t('Cleanup age (for locked processes)'),
    '#description' => t('Seconds to wait before unlocking processes that never started.'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_cleanup_age'),
  ];

  $form['background_process_cleanup_age_running'] = [
    '#type' => 'textfield',
    '#title' => t('Cleanup age (for running processes)'),
    '#description' => t('Unlock processes that has been running for more than X seconds.'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_cleanup_age_running'),
  ];

  $form['background_process_cleanup_age_queue'] = [
    '#type' => 'textfield',
    '#title' => t('Cleanup age for queued jobs'),
    '#description' => t('Unlock queued processes that have been more than X seconds to start.'),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_cleanup_age_queue'),
  ];

  $options = background_process_get_service_hosts();
  foreach ($options as $key => &$value) {
    global $base_url;
    $new = empty($value['description']) ? $key : $value['description'];
    $base_url_link = empty($value['base_url']) ? $base_url : $value['base_url'];
    $http_host = empty($value['http_host']) ? parse_url($base_url, PHP_URL_HOST) : $value['http_host'];
    $new .= ' (' . $base_url_link . ' - ' . $http_host . ')';
    $value = $new;
  }

  $form['background_process_default_service_host'] = [
    '#type' => 'select',
    '#title' => t('Default service host'),
    '#description' => t('The default service host to use'),
    '#options' => $options,
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_default_service_host'),
  ];

  $form['background_process_ssl_verification'] = [
    '#type' => 'checkbox',
    '#title' => t('SSL verification'),
    '#description' => t("Don't turn this off on production environments, as it will raise security issues"),
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_ssl_verification'),
  ];

  $methods = \Drupal::moduleHandler()->invokeAll('service_group');
  $options = background_process_get_service_groups();
  foreach ($options as $key => &$value) {
    $value = (empty($value['description']) ? $key : $value['description']) . ' (' . implode(',', $value['hosts']) . ') : ' . $methods['methods'][$value['method']];
  }

  $form['background_process_default_service_group'] = [
    '#type' => 'select',
    '#title' => t('Default service group'),
    '#description' => t('The default service group to use.'),
    '#options' => $options,
    '#default_value' => \Drupal::config('background_process.settings')->get('background_process_default_service_group'),
  ];

  $form = system_settings_form($form);

  // Add determine button and make sure all the buttons are shown last.
  $form['buttons']['#weight'] = 1000;
  $form['buttons']['determine'] = [
    '#value' => t("Determine default service host"),
    '#description' => t('Tries to determine the default service host.'),
    '#type' => 'submit',
    '#submit' => ['background_process_settings_form_determine_submit'],
  ];

  return $form;
}

/**
 * Implements Submit handler for determining default service host.
 */
function background_process_settings_form_determine_submit($form, &$form_state) {
  background_process_determine_and_save_default_service_host();
}
