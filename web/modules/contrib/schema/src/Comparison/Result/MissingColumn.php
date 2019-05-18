<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\MissingColumn.
 */

namespace Drupal\schema\Comparison\Result;


class MissingColumn extends BaseColumn {
  public function getSchema() {
    return $this->schema;
  }
}
