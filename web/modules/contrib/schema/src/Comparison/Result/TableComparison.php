<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\Result\TableComparison.
 */

namespace Drupal\schema\Comparison\Result;


class TableComparison {
  const STATUS_SAME = 0;
  const STATUS_DIFFERENT = 1;

  protected $name;
  protected $schema;

  protected $table_comment_declared = FALSE;
  protected $table_comment_actual = FALSE;

  protected $fields_diff = array();
  protected $fields_extra = array();
  protected $fields_missing = array();

  protected $index_diff = array();
  protected $index_extra = array();
  protected $index_missing = array();
  protected $status = self::STATUS_SAME;

  public function __construct($name, $schema) {
    $this->name = $name;
    $this->schema = $schema;
  }

  public function getModule() {
    if (isset($this->schema['module'])) {
      return $this->schema['module'];
    }
    return 'unknown';
  }

  public function isTableCommentDifferent() {
    return $this->getActualTableComment() != $this->getDeclaredTableComment();
  }

  public function getActualTableComment() {
    return $this->table_comment_actual;
  }

  public function getDeclaredTableComment() {
    if (!$this->table_comment_declared) {
      $this->table_comment_declared = empty($this->schema['description']) ? FALSE : $this->schema['description'];
    }
    return $this->table_comment_declared;
  }

  public function setActualTableComment($value) {
    if (empty($value)) {
      $value = FALSE;
    }
    $this->table_comment_actual = $value;
  }

  public function setDeclaredTableComment($value) {
    if (empty($value)) {
      $value = FALSE;
    }
    $this->table_comment_declared = $value;
  }

  public function addMissingColumn($field, $definition) {
    $this->status = self::STATUS_DIFFERENT;
    $this->fields_missing[$field] = new MissingColumn($this->getTableName(), $field, $definition);
  }

  public function getTableName() {
    return $this->name;
  }

  public function addExtraColumn($field, $schema) {
    $this->status = self::STATUS_DIFFERENT;
    $this->fields_extra[$field] = new ExtraColumn($this->getTableName(), $field, $schema);
  }

  public function addColumnDifferences($field, $different_keys, $declared_schema, $actual_schema) {
    $this->status = self::STATUS_DIFFERENT;
    $this->fields_diff[$field] = new DifferentColumn($this->getTableName(), $field, $different_keys, $declared_schema, $actual_schema);
  }

  public function addMissingPrimaryKey($definition) {
    $this->addMissingIndex('PRIMARY', 'PRIMARY', $definition);
  }

  public function addMissingIndex($field, $type, $definition) {
    $this->status = self::STATUS_DIFFERENT;
    $this->index_missing[$field] = new MissingIndex($this->getTableName(), $field, $type, $definition);
  }

  public function addExtraPrimaryKey($definition) {
    $this->addExtraIndex('PRIMARY', 'PRIMARY', $definition);
  }

  public function addExtraIndex($field, $type, $schema) {
    $this->status = self::STATUS_DIFFERENT;
    // Other than for the primary key, this is not necessarily an error as the
    // dba might have added the index on purpose for performance reasons.
    $this->index_extra[$field] = new ExtraIndex($this->getTableName(), $field, $type, $schema);
  }

  public function addPrimaryKeyDifference($declared, $actual) {
    $this->addIndexDifferences('PRIMARY', 'PRIMARY', $declared, $actual);
  }

  public function addIndexDifferences($field, $type, $declared_schema, $actual_schema) {
    $this->status = self::STATUS_DIFFERENT;
    $this->index_diff[$field] = new DifferentIndex($this->getTableName(), $field, $type, $declared_schema, $actual_schema);
  }

  public function isStatusSame() {
    return $this->getStatus() == self::STATUS_SAME;
  }

  public function getStatus() {
    return $this->status;
  }

  public function isStatusDifferent() {
    return $this->getStatus() == self::STATUS_DIFFERENT;
  }

  public function getDifferentColumns() {
    return $this->fields_diff;
  }

  public function getExtraColumns() {
    return $this->fields_extra;
  }

  public function getMissingColumns() {
    return $this->fields_missing;
  }

  public function getDeclaredPrimaryKey() {
    return isset($this->schema['primary key']) ? $this->schema['primary key'] : FALSE;
  }

  /**
   * Get all declared indexes for this table.
   *
   * @param bool $include_extra
   *   Also include extra indexes, i.e. indexes which are present in the
   *   database but missing from the declared schema.
   * @return array
   */
  public function getDeclaredIndexes($include_extra = FALSE) {
    $indexes = array(
      'indexes' => isset($this->schema['indexes']) ? $this->schema['indexes'] : array(),
      'unique keys' => isset($this->schema['unique keys']) ? $this->schema['unique keys'] : array(),
    );
    if ($include_extra) {
      /** @var ExtraIndex $index */
      foreach ($this->getExtraIndexes() as $index) {
        $type = $index->getType() == 'UNIQUE' ? 'unique keys' : 'indexes';
        $indexes[$type][$index->getIndexName()] = $index->getSchema();
      }
    }
    return $indexes;
  }

  public function getExtraIndexes() {
    return $this->index_extra;
  }

  public function getMissingIndexes() {
    return $this->index_missing;
  }

  public function getDifferentIndexes() {
    return $this->index_diff;
  }
}
