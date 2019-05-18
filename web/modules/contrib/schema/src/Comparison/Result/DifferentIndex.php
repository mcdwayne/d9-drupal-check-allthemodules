<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\DifferentIndex
 */

namespace Drupal\schema\Comparison\Result;


class DifferentIndex extends BaseIndex {
  protected $actual_schema;

  function __construct($table_name, $index_name, $index_type, $declared_schema, $actual_schema) {
    parent::__construct($table_name, $index_name, $index_type, $declared_schema);
    $this->actual_schema = $actual_schema;
  }

  public function getDeclaredSchema() {
    return $this->schema;
  }

  public function getActualSchema() {
    return $this->actual_schema;
  }

}
