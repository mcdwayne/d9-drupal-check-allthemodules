<?php

/**
 * We cannot use entity controller for managing arbitrary table rows,
 * because data tables do not necessary have primary key(s).
 */

namespace Drupal\data;
use Drupal\Core\Database\Connection;


/**
 * Class RowManager.
 *
 * @package Drupal\data
 */
class RowManager implements RowManagerInterface {

  /** @var  \Drupal\Core\Database\Connection */
  protected $connection;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save($table_name, array $values) {
    $this->connection->insert($table_name, $values);
  }

}
