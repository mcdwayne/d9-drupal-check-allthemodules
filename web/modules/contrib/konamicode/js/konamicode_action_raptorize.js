/**
 * @file
 * JavaScript code for the Raptorize action.
 */

Drupal.konamicode_action_raptorize = function () {
  'use strict';
  let action_data = Drupal.get_action_field_info(drupalSettings.konamicode.actions, 'raptorize');
  if (action_data !== false) {
    let delay = action_data.konamicodeRaptorizeDelay || 50;
    jQuery('body').raptorize({
      'delayTime': delay,
    });
  }
};
