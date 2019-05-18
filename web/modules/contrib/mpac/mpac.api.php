<?php

/**
 * @file
 * Hooks provided by the Multi-path autocomplete module.
 */

/**
 * Declares information about additional selection plugins.
 *
 * Any module can define selection plugins to list custom results in
 * autocomplete fields controlled by Multi-path autocomplete.
 *
 * @return array
 *   An associative array of plugin definitions. The keys of the array are the
 *   IDs of the plugins and each corresponding value is an associative array
 *   with the following key-value pairs:
 *   - 'label': The human readable name of the plugin, which should be passed
 *     through the t() function for translation.
 */
function hook_mpac_selection_plugin_info() {
  return array(
    'test' => array(
      'label' => t('Test selection'),
    ),
  );
}

/**
 * Alters the list of selection handlers for a given type.
 *
 * Called by mpac_get_selection_handlers() to allow modules to alter the list of
 * selection handler for a single selection type (i.e. "path").
 *
 * @param array $handlers
 *   Associative array of selection plugins keyed by the plugin ID.
 * @param string $type
 *   Name of selection type (i.e. "path").
 */
function hook_mpac_selection_handlers(&$handlers, $type) {
  if ($type == 'path' && isset($handlers['node'])) {
    // Load instance of custom plugin.
    $plugin = \Drupal::getContainer()
            ->get('plugin.manager.mpac.selection')
            ->createInstance('test');
    // Replace node selection handler with custom implementation.
    $handlers['node'] = $plugin;
  }
}

/**
 * Alters the list of selection matches.
 *
 * @param array $matches
 *   List of matches provided by the registered selection plugins.
 * @param string $type
 *   Name of selection type (i.e. "path").
 * @param string $string
 *   The string entered by the user to match against.
 */
function hook_mpac_selection_matches(&$matches, $type, $string) {
  if ($type == 'path' && isset($matches['node/1'])) {
    // Remove this item from the list.
    unset($matches['node/1']);
  }
}
