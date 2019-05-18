<?php

/**
 * @file
 * Entity Router API.
 */

/**
 * Alter the list of entity response handler plugins.
 *
 * @param array[] $plugins
 *   The list of plugins, defined by the "EntityResponseHandler" annotation.
 *
 * @code
 * $plugins['id_from_annotation'] = [
 *   // The unique ID of a plugin defined by the annotation.
 *   'id' => 'id_from_annotation',
 *   // The FQN of a class implementing a plugin.
 *   'class' => 'Drupal\my_module\Plugin\EntityResponseHandler\TheAnnotatedPluginClass',
 *   // The name of a module providing a plugin.
 *   'provider' => 'my_module',
 *   // The list of module names a plugin depends on.
 *   'dependencies' => ['jsonapi', 'rest', 'rest_ui'],
 * ];
 * @endcode
 */
function hook_entity_response_handler_plugins_alter(array &$plugins) {
}
