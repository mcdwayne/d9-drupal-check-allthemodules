<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\MissingTable.
 */

namespace Drupal\schema\Comparison\Result;


class MissingTable extends BaseTable {
  public function getSchema() {
    return $this->schema;
  }
}
