/**
 * @file
 * Main Konami Code detection logic.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  $(document).ready(function () {
    let main_settings = drupalSettings.konamicode.main;
    let konami_listeners = [];
    let progress = [];

    // Loop over the main configuration item and add it to the listener array.
    main_settings.forEach(function (item) {
      // Also split the sequence string into an array of strings and cast it to
      // numbers so it's actually an array of keystrokes keys. E.g.
      // [38, 38, 40, 40, 37, 39, 37, 39, 66, 65].
      konami_listeners.push({'callback': item.callback, 'sequence': item.keycode_sequence.split(',').map(Number)});
    });

    // Add a listener for the Key Up event to catch keystrokes.
    $(document).bind("keyup", function (event) {
      progress.push(event.keyCode);

      // Loop over all the listeners and check if we have a match.
      $.each(konami_listeners, function (index, listener) {
        // Only compare if it's a possible full match.
        if (progress.length >= listener.sequence.length) {
          // Create a target sequence that is the same length as the progress.
          let target = progress.slice(progress.length - listener.sequence.length);
          // Check if the result is the same.
          let equals = true;
          for (let i = 0; i < target.length; i++) {
            if (target[i] !== listener.sequence[i]) {
              equals = false;
              break;
            }
          }
          if (equals) {
            // Reset the progress and invoke the listener.
            progress = [];
            // Call the function with the callback from the listener.
            Drupal[listener.callback]();
            return false;
          }
        }
      });

      // Keep the progress length sane.
      if (progress.length > 40) {
        progress = progress.slice(25);
      }
    });

  });
})(jQuery, Drupal, drupalSettings);

/**
 * Function to get all the field info for a specific action.
 *
 * @param action_settings
 *   The full action object.
 * @param action
 *   Machine name of the action.
 *
 * @returns {string|boolean}
 *   False if nothing found, otherwise the data.
 */
Drupal.get_action_field_info = function (action_settings, action) {
  'use strict';
  let result = false;
  action_settings.forEach(function (action_data) {
    if (action_data.machine_name === action) {
      result = action_data;
    }
  });
  return result;
};
