<?php

namespace Drupal\log_monitor;

use Drupal\Core\Database\Driver\mysql\Connection;

class CleanupManager {


  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new CleanupManager object.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Performs cleanup.
   */
  public function clean() {
    $this->removeDependencies();
    $this->removeLogs();
  }

  /**
   * Removes dependencies for expired logs.
   */
  public function removeDependencies() {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('log_monitor_rule')
      ->loadByProperties(['status' => '1']);
    foreach ($entities as $entity) {
      if (LogMonitorHelper::isExpired($entity)) {
        \Drupal::service('log_monitor.dependency_manager')
          ->removeEntityDependencies($entity);
      }
    }
  }

  /**
   * Removes logs which have been processed but are not associated with any rule
   * entity.
   */
  public function removeLogs() {
    $this->database->query(
      'DELETE l
      FROM {log_monitor_log} l
      WHERE l.status IN (1, 3)
      AND l.wid NOT IN 
        (
          SELECT wid
          FROM {log_monitor_log_dependencies}
          WHERE wid IS NOT NULL
        )');
  }

}
