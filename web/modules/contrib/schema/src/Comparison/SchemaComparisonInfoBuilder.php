<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\SchemaComparisonInfoBuilder.
 */

namespace Drupal\schema\Comparison;


use Drupal\schema\Comparison\Result\ExtraTable;
use Drupal\schema\Comparison\Result\MissingTable;
use Drupal\schema\Comparison\Result\SchemaComparison;
use Drupal\schema\Comparison\Result\TableComparison;


class SchemaComparisonInfoBuilder {
  protected $o;

  public function __construct(SchemaComparison $comparison) {
    $this->o = $comparison;
  }

  public function getInfoArray() {
    return $this->getWarningsArray() + $this->getTablesArray();
  }

  public function getWarningsArray() {
    $info = array();
    foreach ($this->o->getWarnings() as $warning) {
      $info['warn'][] = $warning;
    }
    return $info;
  }

  public function getTablesArray() {
    $info = array();

    /** @var MissingTable $table */
    foreach ($this->o->getMissingTables() as $table) {
      $info['missing'][$table->getModule()][$table->getTableName()] = array('status' => 'missing');
    }

    /** @var TableComparison $table */
    foreach ($this->o->getComparedTables() as $table) {
      $table_info = (new TableComparisonInfoBuilder($table))->getInfoArray();
      $info[$table_info["status"]][$table->getModule()][$table->getTableName()] = $table_info;
    }

    /** @var ExtraTable $table */
    foreach ($this->o->getExtraTables() as $table) {
      $info['extra'][] = $table->getTableName();
    }

    return $info;
  }
}
