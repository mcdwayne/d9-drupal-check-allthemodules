<?php

/**
 * @file
 * Administrative page callbacks for the snowplow module.
 */

/**
 * Implements hook_admin_settings(). for module settings configuration.
 */
function snowplow_js_admin_settings_form($form_state) {

  $form['app'] = [
    '#type' => 'fieldset',
    '#title' => t('General settings'),
  ];

  $form['app']['snowplow_app_id'] = [
    '#title' => t('Snowplow App ID'),
    '#type' => 'textfield',
    '#default_value' => variable_get('snowplow_app_id', 'APP-ID'),
    '#size' => 15,
    '#maxlength' => 20,
    '#required' => TRUE,
    '#description' => t('This id will be set on the tracker.'),
  ];

  $form['app']['snowplow_app_endpoint'] = [
    '#title' => t('Endpoint URL'),
    '#type' => 'textfield',
    '#default_value' => variable_get('snowplow_app_endpoint', ''),
    '#size' => 128,
    '#maxlength' => 255,
    '#required' => TRUE,
    '#description' => t('Enter the endpoint where the events will be tracked too.'),
  ];

  // Visibility settings.
  $form['tracking_title'] = [
    '#type' => 'item',
    '#title' => t('Tracking scope'),
  ];

  $form['tracking'] = [
    '#type' => 'vertical_tabs',
    '#attached' => [
      'js' => [drupal_get_path('module', 'snowplow') . '/snowplow.admin.js'],
    ],
  ];

  // Login specifc configuration
  $form['tracking']['login'] = [
    '#type' => 'fieldset',
    '#title' => t('Login'),
  ];

  $form['tracking']['login']['snowplow_track_login'] = [
    '#type' => 'checkbox',
    '#title' => t('Track login events'),
    '#default_value' => variable_get('snowplow_track_login', TRUE),
    '#description' => t('This will track login events to snowplow using php, you need to make sure your frontend js scipt is not also loging these events.'),
  ];

  $form['tracking']['logout'] = [
    '#type' => 'fieldset',
    '#title' => t('Logout'),
  ];

  $form['tracking']['new_user'] = [
    '#type' => 'fieldset',
    '#title' => t('New User'),
  ];

  // Message specific configurations.
  $form['tracking']['messagetracking'] = [
    '#type' => 'fieldset',
    '#title' => t('Messages'),
  ];

  $form['tracking']['messagetracking']['snowplow_track_messages'] = [
    '#type' => 'checkboxes',
    '#title' => t('Track messages of type'),
    '#default_value' => variable_get('snowplow_track_messages', []),
    '#description' => t('This will track the selected message types shown to users. Tracking of form validation errors may help you identifying usability issues in your site. For each visit (user session), a maximum of approximately 500 combined GATC requests (both events and page views) can be tracked. Every message is tracked as one individual event. Note that - as the number of events in a session approaches the limit - additional events might not be tracked. Messages from excluded pages cannot be tracked.'),
    '#options' => [
      'status' => t('Status message'),
      'warning' => t('Warning message'),
      'error' => t('Error message'),
    ],
  ];

  return system_settings_form($form);

}

