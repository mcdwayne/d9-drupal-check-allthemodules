(function ($, window, Drupal, drupalSettings) {
	'use strict';
	var block_counter = drupalSettings.drupal_settings_array.length;
	var drupalSettingsArray;
	var i = 0;
	googletag.cmd.push(function () {
		while (i < block_counter) {
			drupalSettingsArray = drupalSettings.drupal_settings_array[i];
			googletag.display(drupalSettingsArray.slot_matching_string);
			i = i + 1;
		}
	});
})(jQuery, window, Drupal, drupalSettings);
