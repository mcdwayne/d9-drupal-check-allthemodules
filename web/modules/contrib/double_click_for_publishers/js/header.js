(function ($, window, Drupal, drupalSettings) {
	'use strict';
	var block_counter = drupalSettings.drupal_settings_array.length;
	var drupalSettingsArray;
	var i = 0;
	googletag.cmd.push(function () {
		while (i < block_counter) {
			drupalSettingsArray = drupalSettings.drupal_settings_array[i];
			googletag.defineSlot('/' + drupalSettingsArray.network_code + '/' + drupalSettingsArray.targeted_add_unit, [parseInt(drupalSettingsArray.width), parseInt(drupalSettingsArray.height)], drupalSettingsArray.slot_matching_string).addService(googletag.pubads());
			googletag.pubads().enableSingleRequest();
			googletag.enableServices();
			i = i + 1;
		}
	});
})(jQuery, window, Drupal, drupalSettings);
