<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\BaseTable.
 */

namespace Drupal\schema\Comparison\Result;


abstract class BaseTable {
  protected $table_name;
  protected $schema;

  function __construct($table_name, $schema) {
    $this->table_name = $table_name;
    $this->schema = $schema;
  }

  public function getTableName() {
    return $this->table_name;
  }

  public function getModule() {
    if (isset($this->schema['module'])) {
      return $this->schema['module'];
    }
    return t('Unknown');
  }
}
