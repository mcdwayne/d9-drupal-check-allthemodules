/**
 * @file
 * JavaScript code for the Redirect action.
 */

Drupal.konamicode_action_redirect = function () {
  'use strict';
  let action_data = Drupal.get_action_field_info(drupalSettings.konamicode.actions, 'redirect');
  if (action_data !== false) {
    // If we have a destination, we go to it. If not let's rick roll people.
    window.location = action_data.konamicodeRedirectDestination || 'https://youtu.be/dQw4w9WgXcQ';
  }
};
