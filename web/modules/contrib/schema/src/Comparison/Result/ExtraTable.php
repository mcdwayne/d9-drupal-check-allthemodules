<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\ExtraTable.
 */

namespace Drupal\schema\Comparison\Result;


class ExtraTable extends BaseTable {
  public function getSchema() {
    return $this->schema;
  }
}
