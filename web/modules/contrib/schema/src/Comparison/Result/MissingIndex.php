<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\MissingIndex.
 */

namespace Drupal\schema\Comparison\Result;


class MissingIndex extends BaseIndex {

  public function getSchema() {
    return $this->schema;
  }
}
