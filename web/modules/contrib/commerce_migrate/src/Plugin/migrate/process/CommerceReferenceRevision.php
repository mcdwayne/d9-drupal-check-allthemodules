<?php

namespace Drupal\commerce_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Migrate reference fields.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce_migrate_commerce_reference_revision"
 * )
 */
class CommerceReferenceRevision extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      // Convert the value into a non-scalar value, so the parent method will
      // return our values properly.
      $value = [$value];
    }
    $ids = parent::transform($value, $migrate_executable, $row, $destination_property);
    $target_id = $ids[0];
    $revision_id = $ids[1];
    return ['target_id' => $target_id, 'target_revision_id' => $revision_id];
  }

}
