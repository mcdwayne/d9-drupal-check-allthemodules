<?php

namespace Drupal\Driver\Database\oracle;

use Drupal\Core\Database\Statement as DatabaseStatement;

/**
 * Oracle implementation of \Drupal\Core\Database\Query\Update.
 */
class Statement extends DatabaseStatement implements \IteratorAggregate {

  /**
   * Reference to the database connection object for this statement.
   *
   * The name $dbh is inherited from \PDOStatement.
   *
   * @var \Drupal\Driver\Database\oracle\Connection
   */
  public $dbh;

  /**
   * {@inheritdoc}
   */
  public function execute($args = array(), $options = array()) {
    if (!is_array($args) || !count($args)) {
      $args = NULL;
    }
    return parent::execute($args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($fetch_style = NULL, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
    return $this->dbh->cleanupFetched(parent::fetch($fetch_style, $cursor_orientation, $cursor_offset));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchField($index = 0) {
    return $this->dbh->cleanupFetched(parent::fetchField($index));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchObject($class_name = "stdClass", array $ctor_args = array()) {
    return $this->dbh->cleanupFetched(parent::fetchObject($class_name, $ctor_args));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    return $this->dbh->cleanupFetched(parent::fetchAssoc());
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $ctor_args = NULL) {
    return $this->dbh->cleanupFetched(parent::fetchAll($mode));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCol($index = 0) {
    return $this->dbh->cleanupFetched(parent::fetchAll(\PDO::FETCH_COLUMN, $index));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchColumn($column_number = 0) {
    return $this->dbh->cleanupFetched(parent::fetchColumn($column_number));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllKeyed($key_index = 0, $value_index = 1) {
    return $this->dbh->cleanupFetched(parent::fetchAllKeyed($key_index, $value_index));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch_style = NULL) {
    return $this->dbh->cleanupFetched(parent::fetchAllAssoc($key, $fetch_style));
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->fetchAll());
  }

}
