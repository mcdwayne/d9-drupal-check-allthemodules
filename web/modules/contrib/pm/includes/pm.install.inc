<?php
/**
 * @file
 * Helper functions for install, disable, uninstall and update of PM modules.
 */

/**
 * Sets a standardised message on PM module disable.
 */
function pm_disable_message($module, $entity_name) {
  $module_info = system_get_info('module', $module);

  $message = t(
    '@module_name has been disabled. Fields attached to @entity_name will remain until the module is uninstalled.',
    array(
      '@module_name' => $module_info['name'],
      '@entity_name' => $entity_name,
    )
  );

  drupal_set_message($message);
}

/**
 * Sets a standardised message on PM module uninstall.
 */
function pm_uninstall_message($module, $entity_name) {
  $message = t(
    '@module_name has been uninstalled. Fields attached to @entity_name have been deleted.',
    array(
      // The human readable name is not available after disabling the module.
      '@module_name' => $module,
      '@entity_name' => $entity_name,
    )
  );

  drupal_set_message($message);
}

/**
 * Deletes variables set by a specified module.
 */
function pm_uninstall_variables($module) {
  // All variables in the module namespace.
  db_delete('variable')
    ->condition('name', $module . '_%', 'LIKE')
    ->execute();

  // Content type variables.
  variable_del('node_options_' . $module);

  // PM Permission configuration for the specified module.
  variable_del('pm_permission_node_' . $module . '_enabled');
  variable_del('pm_permission_field_parent_reference_for_' . $module);
}

/**
 * Deletes field instances and bases from a given list.
 */
function pm_uninstall_fields($module) {
  // Determine which fields were declared by the specified module.
  module_load_include('inc', $module, 'includes/' . $module . '.field_base');
  module_load_include('inc', $module, 'includes/' . $module . '.field_instance');
  $field_bases = module_invoke($module, 'default_field_bases');
  $field_instances = module_invoke($module, 'default_field_instances');

  foreach ($field_instances as $instance) {
    // Only attempt to delete instances that still exist.
    $instance_info = field_info_instance($instance['entity_type'], $instance['field_name'], $instance['bundle']);

    if ($instance_info) {
      // Only delete field base if provided in the list of bases.
      $delete_field_base = isset($field_bases[$instance['field_name']]);
      field_delete_instance($instance, $delete_field_base);
    }
  }
}
