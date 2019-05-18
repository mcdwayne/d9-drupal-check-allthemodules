<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\BaseIndex.
 */

namespace Drupal\schema\Comparison\Result;


abstract class BaseIndex {

  protected $table_name;
  protected $index_name;
  protected $index_type;
  protected $schema;

  public function __construct($table_name, $index_name, $index_type, $schema) {
    $this->table_name = $table_name;
    $this->index_name = $index_name;
    $this->index_type = $index_type;
    $this->schema = $schema;
  }

  public function getType() {
    return $this->index_type;
  }

  public function getTableName() {
    return $this->table_name;
  }

  public function getIndexName() {
    return $this->index_name;
  }

  public function isPrimary() {
    return $this->index_type == 'PRIMARY';
  }

  public function getModule() {
    if (isset($this->schema['module'])) {
      return $this->schema['module'];
    }
    return t('Unknown');
  }

}
