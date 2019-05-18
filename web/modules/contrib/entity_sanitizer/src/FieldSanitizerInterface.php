<?php

namespace Drupal\entity_sanitizer;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface definition for field sanitizer plugins.
 *
 * @ingroup entity_sanitizer
 */
interface FieldSanitizerInterface extends PluginInspectionInterface {

  /**
   * Get the sanitizing field values for a field in a table.
   *
   * @param $table_name
   *   The table name to which the sanitizing is applied.
   * @param $field_name
   *   The name of the field we're sanitizing.
   * @param $columns
   *   Column definitions for the field that's being sanitized, provides
   *   information about things like maximum field length.
   *
   * @return array
   *   An array whose key/value pairs correspond to the parameters of the
   *   Update::expression() function.
   */
  public function getFieldValues($table_name, $field_name, $columns);
}
