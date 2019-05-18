/**
 * @file
 * JavaScript code for the Alert action.
 */

Drupal.konamicode_action_alert = function () {
  'use strict';
  let action_data = Drupal.get_action_field_info(drupalSettings.konamicode.actions, 'alert');
  if (action_data !== false) {
    // Load the message otherwise we use a default value.
    let message = action_data.konamicodeAlertMessage || 'Konami Code Is Geek';
    alert(message);
  }
};
