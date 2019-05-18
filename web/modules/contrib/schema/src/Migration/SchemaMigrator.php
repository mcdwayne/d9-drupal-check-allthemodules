<?php
/**
 * @file
 * Contains Drupal\schema\Migration\SchemaMigrator.
 */

namespace Drupal\schema\Migration;

use Drupal\schema\Comparison\Result\DifferentColumn;
use Drupal\schema\Comparison\Result\ExtraColumn;
use Drupal\schema\Comparison\Result\SchemaComparison;
use Drupal\schema\Comparison\Result\TableComparison;
use Drupal\schema\DatabaseSchemaInspectionInterface;

/**
 * Modifies the database schema to match the declared schema.
 */
class SchemaMigrator {

  /**
   * @var SchemaComparison
   */
  protected $comparison;

  /**
   * @var SchemaMigratorOptions
   */
  protected $options;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @param SchemaComparison $comparison
   * @param DatabaseSchemaInspectionInterface $dbschema
   * @param SchemaMigratorOptions $options
   */
  public function __construct(SchemaComparison $comparison, DatabaseSchemaInspectionInterface $dbschema, SchemaMigratorOptions $options = NULL) {
    $this->comparison = $comparison;
    $this->dbschema = $dbschema;

    $this->options = $options;
    if ($this->options == NULL) {
      $this->options = new SchemaMigratorOptions();
    }

    $this->logger = \Drupal::logger('schema');
  }

  public function execute() {
    $tables = $this->getTargetTables();
    if ($this->options()->useDifferentTables) {
      $tables += $this->comparison->getDifferentTables();
    }
    if ($this->options()->useSameTables) {
      $tables += $this->comparison->getSameTables();
    }

    /** @var TableComparison $table */
    foreach ($tables as $table) {
      if ($this->options()->fixTableComments) {
        $this->fixTableComment($table);
      }
      if ($this->options()->addMissingColumns) {
        throw new \Exception("Adding missing columns not implemented yet.");
      }
      if ($this->options()->updateColumnProperties) {
        $this->updateColumnProperties($table);
      }
      if ($this->options()->removeExtraColumns) {
        $this->removeExtraColumns($table);
      }
      if ($this->options()->recreatePrimaryKey) {
        $this->recreatePrimaryKey($table);
      }
      if ($this->options()->recreateIndexes) {
        $this->recreateIndexes($table);
      }
    }
  }

  protected function getTargetTables() {
    $tables = array();
    if ($this->options()->useDifferentTables) {
      $tables += $this->comparison->getDifferentTables();
    }
    if ($this->options()->useSameTables) {
      $tables += $this->comparison->getSameTables();
    }
    return $tables;
  }

  public function options() {
    return $this->options;
  }

  protected function fixTableComment(TableComparison $table) {
    if ($table->isTableCommentDifferent()) {
      $this->dbschema->updateTableComment($table->getTableName(), $table->getDeclaredTableComment());
      $this->logSuccess("Updated comment for {table} to '{comment}'.", array(
        'table' => $table->getTableName(),
        'comment' => $table->getDeclaredTableComment(),
      ));
    }
  }

  protected function logSuccess($message, $context) {
    if (function_exists('drush_log')) {
      drush_log($this->logMessageInterpolate($message, $context), 'success');
    }
    $this->logger->info($message, $context);
  }

  /**
   * Interpolates context values into the message placeholders.
   */
  protected function logMessageInterpolate($message, array $context = array()) {
    // build a replacement array with braces around the context keys
    $replace = array();
    foreach ($context as $key => $val) {
      $replace['{' . $key . '}'] = $val;
    }

    // interpolate replacement values into the message and return
    return strtr($message, $replace);
  }

  /**
   * @param $table TableComparison
   */
  protected function updateColumnProperties($table) {
    $differences = $table->getDifferentColumns();
    if (!empty($differences)) {
      /** @var DifferentColumn $column */
      foreach ($differences as $column) {
        // The schema comparator has already determined that the field exists
        // and that at least some of the properties are different.+
        // @todo Update respective indices at the same time; otherwise this will fail for primary keys.
        $this->dbschema->changeField($column->getTableName(), $column->getColumnName(), $column->getColumnName(), $column->getDeclaredSchema());

        $this->logSuccess("Changed column {table}.{field} definition to {schema}.", array(
          'table' => $column->getTableName(),
          'field' => $column->getColumnName(),
          'schema' => '[' . $this->schemaString($column->getDeclaredSchema()) . ']',
        ));
      }
    }
  }

  protected function schemaString($schema) {
    return implode(', ', array_map(function ($k, $v) {
      return $k . '=' . $v;
    }, array_keys($schema), $schema));
  }

  /**
   * @param $table TableComparison
   */
  protected function removeExtraColumns($table) {
    $extra_columns = $table->getExtraColumns();
    if (!empty($extra_columns)) {
      /** @var ExtraColumn $column */
      foreach ($extra_columns as $column) {
        if ($this->dbschema->dropField($column->getTableName(), $column->getColumnName())) {
          $this->logSuccess("Dropped column {table}.{field}.", array(
            'table' => $column->getTableName(),
            'field' => $column->getColumnName(),
          ));
        }
        else {
          $this->logError("Tried to drop non-existent field {table}.{field}.", array(
            'table' => $column->getTableName(),
            'field' => $column->getColumnName(),
          ));
        }
      }
    }
  }

  protected function logError($message, $context) {
    if (function_exists('drush_log')) {
      drush_log($this->logMessageInterpolate($message, $context), 'error');
    }
    $this->logger->error($message, $context);
  }

  /**
   * @param $table TableComparison
   */
  protected function recreatePrimaryKey($table) {
    $primary_key = $table->getDeclaredPrimaryKey();
    $msg_args = array(
      'table' => $table->getTableName(),
      'key' => is_array($primary_key) ? implode(', ', $primary_key) : '[]',
    );

    // If primary key exists already, recreate it.
    if ($this->dbschema->indexExists($table->getTableName(), 'PRIMARY') && is_array($primary_key)) {
      $this->dbschema->recreatePrimaryKey($table->getTableName(), $primary_key);
      $this->logSuccess("Recreated primary key for {table} on {key}.", $msg_args);
    }

    // If primary key exists, but we don't want to have one, try to drop it.
    elseif ($this->dbschema->indexExists($table->getTableName(), 'PRIMARY')) {
      log_statement("TABLE %s DROP PRIMARY KEY", $table->getTableName());
      if ($this->dbschema->dropPrimaryKey($table->getTableName())) {
        $this->logSuccess("Dropped primary key for {table}.", $msg_args);
      }
      else {
        $this->logError("Failed to drop primary key for {table}.", $msg_args);
      }
    }

    // If primary key doesn't exit, try to create it.
    elseif (is_array($primary_key)) {
      $this->dbschema->addPrimaryKey($table->getTableName(), $primary_key);
      $this->logSuccess("Created primary key for {table} on {key}.", $msg_args);
    }
  }

  /**
   * @param $table TableComparison
   */
  protected function recreateIndexes($table) {
    // Recreate indices by first removing all, then adding them one by one.
    $existing = $this->dbschema->getIndexes($table->getTableName());
    $count = 0;
    foreach ($existing as $index) {
      $this->dbschema->dropIndex($table->getTableName(), $index);
      $this->logSuccess("Dropped index {index} from {table}.", array(
        'table' => $table->getTableName(),
        'index' => $index,
      ));
      $count++;
    }
    if ($count > 0) {
      $this->logSuccess("Dropped {num} existing indexes from {table}.", array(
        'table' => $table->getTableName(),
        'num' => $count,
      ));
    }

    $indexes = $table->getDeclaredIndexes($this->options()->recreateExtraIndexes);
    foreach ($indexes['indexes'] as $i_name => $fields) {
      $this->dbschema->addIndex($table->getTableName(), $i_name, $fields);
      $this->logSuccess("Added index {index} to {table} on {keys}.", array(
        'table' => $table->getTableName(),
        'index' => $i_name,
        'keys' => '[' . implode(', ', $fields) . ']',
      ));
    }
    foreach ($indexes['unique keys'] as $i_name => $fields) {
      $this->dbschema->addUniqueKey($table->getTableName(), $i_name, $fields);
      $this->logSuccess("Added index {index} to {table} on {keys}.", array(
        'table' => $table->getTableName(),
        'index' => $i_name,
        'keys' => '[' . implode(', ', $fields) . ']',
      ));
    }
  }

}

