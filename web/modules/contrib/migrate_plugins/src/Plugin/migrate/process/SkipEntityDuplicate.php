<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;

/**
 * Skip row processing if duplicate entity is found.
 *
 * @MigrateProcessPlugin(
 *  id = "skip_entity_duplicate"
 * )
 *
 * Example usage with full configuration:
 * @code
 *   field_tags:
 *     plugin: skip_entity_duplicate
 *     source: tags
 *     value_key: name
 *     bundle_key: vid
 *     bundle: tags
 *     entity_type: taxonomy_term
 *     ignore_case: true
 * @endcode
 */
class SkipEntityDuplicate extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $result = parent::transform($value, $migrate_executable, $row, $destination_property);

    // Skip if duplicate was found.
    if ($result) {
      $message = "Skipped $value item that duplicates with entities:\n" . print_r($result, 1);
      throw new MigrateSkipRowException($message);
    }
  }

}
