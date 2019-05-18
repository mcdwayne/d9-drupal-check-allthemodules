<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\ColumnDifference.
 */

namespace Drupal\schema\Comparison\Result;


class DifferentColumn extends BaseColumn {
  protected $different_keys;
  protected $declared_schema;
  protected $actual_schema;

  public function __construct($table_name, $column_name, $different_keys, $declared_schema, $actual_schema) {
    parent::__construct($table_name, $column_name, $declared_schema);
    $this->actual_schema = $actual_schema;
    $this->column_name = $column_name;
    $this->different_keys = $different_keys;
    $this->table_name = $table_name;
  }

  public function getActualSchema() {
    return $this->actual_schema;
  }

  public function getDeclaredSchema() {
    return $this->schema;
  }

  public function getDifferentKeys() {
    return $this->different_keys;
  }
}
