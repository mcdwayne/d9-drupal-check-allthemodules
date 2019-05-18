<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\ExtraColumn.
 */

namespace Drupal\schema\Comparison\Result;


class ExtraColumn extends BaseColumn {
  public function getSchema() {
    return $this->schema;
  }
}
