<?php

/**
 * @file
 *    Hooks provided by the Restrict IP module.
 */

/**
 * Add regions to be whitelisted even when the user has been denied access
 *
 * @return
 *   An array of keys representing regions to be allowed even when
 *   the user is denied access by IP. These keys can be found by
 *   in the .info file for the theme, as region[KEY] = Region Name
 *   where KEY is the key to be returned in the return array
 *   of this function.
 */
function hook_restrict_ip_whitelisted_regions()
{
	return ['sidebar_first'];
}

/**
 * Add js keys to be whitelisted even when the user has been denied access
 *
 * @return
 *   An array of keys representing javascript files to be allowed even when
 *   the user is denied access by IP. These keys can be found by
 *   as the keys in hook_js_alter().
 */
function hook_restrict_ip_whitelisted_js_keys()
{
	return ['core/assets/vendor/jquery/jquery.js'];
}

/**
 * Alter the Restrict IP Access Denied page.
 *
 * @param $page
 *   The render array for the access deneid page passed by reference.
 */
function hook_restrict_ip_access_denied_page_alter(&$page)
{
	$page['additional_information'] = [
		'#markup' => t('Additional information to be shown on the Restrict IP Access Denied page'),
		'#prefix' => '<p>',
		'#suffix' => '</p>',
	];
}
