<?php

namespace Drupal\entity_sanitizer;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for 'Field Sanitizer' plugin implementations.
 *
 * Performs no sanitizing on any fields.
 *
 * @ingroup entity_sanitizer
 */
abstract class FieldSanitizerBase extends PluginBase implements FieldSanitizerInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($table_name, $field_name, $columns) {
    return [];
  }
}
