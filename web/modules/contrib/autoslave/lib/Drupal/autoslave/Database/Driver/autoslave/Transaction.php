<?php

/**
 * @file
 * Definition of Drupal\autoslave\Database\Driver\autoslave\Transaction
 */

namespace Drupal\autoslave\Database\Driver\autoslave;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Transaction as DatabaseTransaction;

/**
 * Handle force master for transactions
 */
class Transaction extends DatabaseTransaction {
  protected $parent;

  public function __construct(DatabaseConnection $connection, $name = NULL) {
    $this->connection = $connection;
    $this->connection->forceMaster(1);
    $this->parent = $this->connection->getMasterConnection()->startTransaction($name);
  }

  public function __destruct() {
    unset($this->parent);
    $this->connection->forceMaster(-1);
    // If tables were flagged inside a transaction, update their timestamp to the commit time.
    if ($this->connection->transactionDepth() == 0) {
      // We need to check if the array exist, just in case some opened and closed a transaction without
      // performing any write queries.
      $key = $this->connection->getKey();
      $target = $this->connection->getTarget();
      if ($this->connection->getReplicationLag()) {
         $affected_tables = &$this->connection->__affected_tables;
      }
      elseif (!empty($_SESSION['autoslave_affected_tables'])) {
        $affected_tables = &$_SESSION['autoslave_affected_tables'];
      }
      else {
        $affected_tables = array();
      }

      if (!empty($affected_tables[$key][$target])) {
        foreach ($affected_tables[$key][$target] as $table => &$expires) {
          // Don't rewrite expiration time for table, if commit happened within the same second
          $new_expires = time() + $this->connection->getReplicationLag();
          if ($new_expires <= $expires) {
            continue;
          }
          $expires = $new_expires;
          $this->connection->max_expires = $this->connection->max_expires < $expires ? $expires : $this->connection->max_expires;
          if ($this->connection->getGlobalReplicationLag()) {
            try {
              $conn = Database::getConnection($this->connection->determineSystemTarget(), 'default');
              $conn->update('autoslave_affected_tables')
                ->fields(array('expires' => $expires))
                ->condition('db_key', $key)
                ->condition('db_target', $target)
                ->condition('affected_table', $table)
                ->execute();
            }
            catch (Exception $e) {
              // Just ignore error for now
            }
          }
        }
        if ($this->connection->max_expires) {
          $_SESSION['ignore_slave_server'] = $this->connection->max_expires;
        }
      }

      // Clear cache buffer
      if (is_callable(array('AutoslaveCache', 'clearBuffer'))) {
        AutoslaveCache::clearBuffer();
      }

    }
  }

  public function name() { return $this->parent->name(); }
  public function rollback() { return $this->parent->rollback(); }
}

