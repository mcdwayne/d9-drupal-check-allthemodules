<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'FieldValueDeltas' migrate process plugin.
 *
 * This plugin helps to set a delta value on multi-cardinality
 * fiel so each value array item has a delta, the primary use
 * is in conjunction with d7_field_data plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "field_value_deltas",
 *   handle_multiples = TRUE
 * )
 */
class FieldValueDeltas extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Skip non array values.
    if (!is_array($value)) {
      return $value;
    }

    for ($i = 0; $i < count($value); ++$i) {
      $value[$i]['delta'] = $i;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
