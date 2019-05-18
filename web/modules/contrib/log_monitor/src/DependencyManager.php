<?php

namespace Drupal\log_monitor;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class DependencyManager.
 *
 * @package Drupal\log_monitor
 */
class DependencyManager {

  const STATUS_INIT = 0;

  const STATUS_HOLD = 1;

  const STATUS_CLEAR = 2;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Constructs a new DependencyManager object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function addDependency($log_id, $entity) {
    try {
      $this->database
        ->insert('log_monitor_log_dependencies')
        ->fields([
          'wid' => $log_id,
          'entity_id' => $entity->id(),
          'status' => self::STATUS_INIT,
        ])
        ->execute();
    }
    catch (\Exception $e) {

    }
  }

  public function removeDependency($log_id, $entity) {
    try {
      $this->database->delete('log_monitor_log_dependencies')
        ->condition('wid', $log_id)
        ->condition('entity_id', $entity->id())
        ->execute();
    }
    catch (\Exception $e) {

      }
  }

  public function removeDependencies($log_id) {
    try {
      $this->database->delete('log_monitor_log_dependencies')
        ->condition('wid', $log_id)
        ->execute();
    }
    catch (\Exception $e) {

    }
  }

  public function removeEntityDependencies($entity) {
    try {
      $this->database->delete('log_monitor_log_dependencies')
        ->condition('entity_id', $entity->id())
        ->execute();
    }
    catch (\Exception $e) {

    }
  }

  public function getDependencies($log_id) {
    return $this->database->select('log_monitor_log_dependencies', 'lmld')
      ->condition('wid', $log_id)
      ->addField('lmld', 'entity_id')
      ->execute()->fetchCol();
  }

  public function getDependentLogs($entity_id) {

  }

}
