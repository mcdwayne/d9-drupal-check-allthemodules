<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\drupal_yext\traits\Singleton;

/**
 * A source record factory. Returns an source record.
 */
class YextSourceRecordFactory {

  use Singleton;

  /**
   * Get a NodeMigrateSourceInterface based on a structure.
   *
   * @param array $structure
   *   An array returned by Yext, or an empty array to ignore the source record.
   *
   * @return NodeMigrateSourceInterface
   *   Can be used for migration.
   */
  public function sourceRecord(array $structure) : NodeMigrateSourceInterface {
    if (!empty($structure['timestamp']) && !empty($structure['id'])) {
      return new YextSourceRecord($structure);
    }
    else {
      return new YextIgnoreSourceRecord();
    }
  }

}
