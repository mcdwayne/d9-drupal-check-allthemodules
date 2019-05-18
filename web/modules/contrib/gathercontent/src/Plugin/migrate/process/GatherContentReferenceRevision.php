<?php

namespace Drupal\gathercontent\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Perform custom value transformation.
 * Converts simple arrays to multidimensional so we can use extract plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "gather_content_reference_revision",
 *   handle_multiples = TRUE
 * )
 *
 * @code
 * reference_revision_field:
 *   plugin: gather_content_reference_revision
 *   source: field
 * @endcode
 */
class GatherContentReferenceRevision extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      $value = [$value];
    }

    if (is_array($value)) {
      $return = [];

      foreach ($value as $data) {
        $return[] = ['id' => $data];
      }

      return $return;
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
