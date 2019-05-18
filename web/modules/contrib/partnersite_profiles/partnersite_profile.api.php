<?php
/**
* @file
* Hooks specific to the partnersite_profile module.
*/


/**
 * Alter the definitions of all the Link generator plugins.
 *
 * You can implement this hook to do things like change the properties for each
 * plugin or change the implementing class for a plugin.
 *
 * This hook is invoked by LinkGeneratorManager::__construct().
 *
 * @param array $linkgenerator_plugin_info
 *   This is the array of plugin definitions.
 */

function hook_link_generator_info(array $linkgenerator_plugin_info) {
	foreach ($linkgenerator_plugin_info as $plugin_id => $plugin_definition) {
		$linkgenerator_plugin_info[$plugin_id]['foobar'] = t('We have altered this in the alter hook');
	}
}