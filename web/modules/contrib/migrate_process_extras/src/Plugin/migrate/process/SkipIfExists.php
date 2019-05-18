<?php

namespace Drupal\migrate_process_extras\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Skip the row if the entity exists.
 *
 * Warning, if you use this in a normal migration workflow, it was always skip
 * every row on the second import.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_if_exists"
 * )
 */
class SkipIfExists extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $result = $this->lookupEntity($this->configuration['entity_type_id'], $this->configuration['bundle'] ?? FALSE, $this->configuration['field_name'], $value);
    if ($result) {
      throw new MigrateSkipRowException();
    }
    return $value;
  }

}
