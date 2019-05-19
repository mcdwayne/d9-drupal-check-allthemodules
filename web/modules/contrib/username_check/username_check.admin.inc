<?php

/**
 * @file
 * Admin page callbacks for the username_check module.
 */

/**
 * Menu callback; displays the username_check module settings page.
 */
function username_check_settings() {
  $form = array();


  $form['username_check_mode'] = array(
    '#type' => 'radios',
    '#title' => t('Check for usernames pre-existing in system:'),
    '#options' => array(
      'auto'   => t('On - executes when user leaves username field or upon timer end'),
      'off' => t('Off - No Username checking')),
    '#default_value' => \Drupal::config('username_check.settings')->get('username_check_mode'),
  );

  $form['username_check_mail_mode'] = array(
    '#type' => 'radios',
    '#title' => t('Check for E-mail addresses pre-existing in system:'),
    '#options' => array(
      'auto'   => t('On - executes when user leaves e-mail field or upon timer end'),
      'off' => t('Off - No E-mail address checking')),
    '#default_value' => \Drupal::config('username_check.settings')->get('username_check_mail_mode'),
  );

  $form['username_check_delay'] = array(
    '#type' => 'textfield',
    '#title' => t('Timer threshold:'),
    '#description' => t('Threshold in seconds (ex: 0.5, 1) for the check to happen.'),
    '#default_value' => \Drupal::config('username_check.settings')->get('username_check_delay'),
  );




  return system_settings_form($form);
}