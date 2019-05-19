<?php

/**
 * @file
 * Way2SMS Configuration form settings.
 */

/**
 * Configuration form to set credentials.
 */
function way2sms_form($form, &$form_state) {

  $form['way2sms_senders_phone_number'] = [
    '#type' => 'textfield',
    '#title' => t("Sender's contact number"),
    '#description' => t('Enter the registered contact number at way2sms. For example: 7722800915. Note: Do not use +91, 0.'),
    '#default_value' => variable_get('way2sms_senders_phone_number'),
    '#element_validate' => ['element_validate_number'],
    '#size' => 10,
    '#maxlength' => 10,
    '#required' => TRUE,
  ];
  $form['way2sms_senders_password'] = [
    '#type' => 'password',
    '#title' => t('Way2SMS account password'),
    '#description' => t('Enter login password as your way2sms password. Note: You need to have an account to way2sms.'),
    '#size' => 10,
    '#required' => TRUE,
  ];

  $form['#validate'][] = '_way2sms_form_validate_admin_credentials';

  return system_settings_form($form);
}

/**
 * Validate Entered Credentials.
 */
function _way2sms_form_validate_admin_credentials($form, &$form_state) {
  if ($form_state['values']['way2sms_senders_phone_number'] && $form_state['values']['way2sms_senders_password']) {
    if (!_way2sms_login($form_state['values']['way2sms_senders_phone_number'], $form_state['values']['way2sms_senders_password'])) {
      form_set_error('way2sms_senders_phone_number', t('Please provide valid Credentials'));
    }
  }
}
