<?php
/**
 * @file
 * Views related hooks.
 */

/**
 * Implements hook_views_plugins().
 */
function og_sm_access_views_plugins() {
  $plugins = array(
    'access' => array(
      'test' => array(
        'title' => t('Access to current Site'),
        'help' => t('Checks if the user has access to the current Site.'),
        'handler' => 'og_sm_access_site_plugin',
      ),
    ),
  );
  return $plugins;
}
