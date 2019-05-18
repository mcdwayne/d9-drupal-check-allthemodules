<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\SchemaComparison.
 */

namespace Drupal\schema\Comparison\Result;

/**
 * Stores the results of a schema comparison.
 *
 * @see Drupal\schema\Comparison\SchemaComparator
 */
class SchemaComparison {
  protected $warnings;

  protected $tables_extra = array();
  protected $tables_missing = array();
  protected $tables_compared = array();

  public function addWarning($warning) {
    $this->warnings[] = $warning;
  }

  public function addMissingTable($name, $definition) {
    $this->tables_missing[$name] = new MissingTable($name, $definition);
  }

  public function addExtraTable($name, $schema) {
    $this->tables_extra[$name] = new ExtraTable($name, $schema);
  }

  /**
   * @param $name
   * @param null $schema
   * @return TableComparison
   */
  public function getTableComparison($name, $schema = NULL) {
    if (!isset($this->tables_compared[$name])) {
      $this->tables_compared[$name] = new TableComparison($name, $schema);
    }
    return $this->tables_compared[$name];
  }

  public function getTableNames() {
    $tables = array_merge(
      array_keys($this->tables_extra),
      array_keys($this->tables_missing),
      array_keys($this->tables_compared)
    );
    return $tables;
  }

  public function getComparedTables() {
    return $this->tables_compared;
  }

  public function getSameTables() {
    return array_filter($this->tables_compared, function ($table) {
      /** @var $table TableComparison */
      return $table->isStatusSame();
    });
  }

  public function getDifferentTables() {
    return array_filter($this->tables_compared, function ($table) {
      /** @var $table TableComparison */
      return $table->isStatusDifferent();
    });
  }

  public function getWarnings() {
    return $this->warnings;
  }

  public function getMissingTables() {
    return $this->tables_missing;
  }

  public function getExtraTables() {
    return $this->tables_extra;
  }

}
