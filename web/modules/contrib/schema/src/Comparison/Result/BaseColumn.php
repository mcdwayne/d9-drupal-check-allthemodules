<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\BaseColumn.
 */

namespace Drupal\schema\Comparison\Result;


abstract class BaseColumn {
  protected $column_name;
  protected $table_name;
  protected $schema;

  function __construct($table_name, $column_name, $schema) {
    $this->table_name = $table_name;
    $this->column_name = $column_name;
    $this->schema = $schema;
  }

  public function getTableName() {
    return $this->table_name;
  }

  public function getColumnName() {
    return $this->column_name;
  }

  public function getModule() {
    if (isset($this->schema['module'])) {
      return $this->schema['module'];
    }
    return t('Unknown');
  }
}
