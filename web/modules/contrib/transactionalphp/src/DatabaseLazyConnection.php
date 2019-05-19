<?php

namespace Drupal\transactionalphp;

use Drupal\Core\Database\Database;

/**
 * Class DatabaseLazyConnection.
 *
 * @package Drupal\transactionalphp
 *
 * @codeCoverageIgnore
 */
class DatabaseLazyConnection {
  /**
   * The database target.
   *
   * @var string
   */
  protected $target;

  /**
   * The database key.
   *
   * @var string
   */
  protected $key;

  /**
   * DatabaseLazyConnection constructor.
   *
   * @param string $target
   *   The database target.
   * @param string $key
   *   (optional) The database key.
   */
  public function __construct($target = 'default', $key = NULL) {
    $this->target = $target;
    $this->key = isset($key) ? $key : DatabaseExtender::getActiveKey();
  }

  /**
   * Get database key.
   *
   * @return string
   *   The database key.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Get database target.
   *
   * @return string
   *   The database target.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Check if connection is currently connected.
   *
   * @return bool
   *   TRUE if connection is connected.
   */
  public function isConnected() {
    return DatabaseExtender::isConnected($this->target, $this->key);
  }

  /**
   * Get real database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getConnection() {
    return Database::getConnection($this->target, $this->key);
  }

  /**
   * Get transaction depth.
   *
   * @return int
   *   The transaction depth. 0 if connection is not connected.
   */
  public function transactionDepth() {
    return $this->isConnected() ? $this->getConnection()->transactionDepth() : 0;
  }

}
