<?php
/**
 * @file
 * Migration helper for the PM Issue module.
 */

/**
 * Creates and attaches fields to Drupal user.
 */
function pmissue_migrate_create_fields() {
  module_load_include('inc', 'pmissue', 'includes/pmissue.field_base');
  module_load_include('inc', 'pmissue', 'includes/pmissue.field_instance');
  module_load_include('inc', 'pm', 'includes/pm.field');

  $field_bases = pmissue_default_field_bases();
  pm_field_bases_create_if_required($field_bases);

  $field_instances = pmissue_default_field_instances();
  pm_field_instances_create_if_required($field_instances);
  return TRUE;
}
