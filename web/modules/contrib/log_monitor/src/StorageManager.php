<?php

namespace Drupal\log_monitor;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\log_monitor\Logger\LogMonitorLog;

/**
 * Class StorageManager.
 *
 * @package Drupal\log_monitor
 */
class StorageManager {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Constructs a new StorageManager object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function processLogQueue() {
    $this::claimPendingItems();
    $this::matchClaimedItems();
//    $this::deleteClaimedItems();
    $this::addClaimedItemDependencies();
    $this::completeMatchedItems();
  }

  public function claimPendingItems() {
    //$this->database->update('log_monitor_log')
    //  ->fields(['status' => LogMonitorLog::STATUS_NEEDS_VALIDATION])->execute();

    //@todo: determine if we need a way to expire claimed items or associate them with a specific process.
    $this->database->update('log_monitor_log')
      ->fields(['status' => LogMonitorLog::STATUS_CLAIMED])
      ->condition('status', LogMonitorLog::STATUS_NEEDS_VALIDATION)
      ->execute();
  }

  public function matchClaimedItems() {
    $query = $this->database->update('log_monitor_log')
      ->condition('status', LogMonitorLog::STATUS_CLAIMED)
      ->fields(['status' => LogMonitorLog::STATUS_MATCHED]);

    if($conditions = $this->getQueryConditions($query)) {
      $group = $query->orConditionGroup();
      foreach($conditions as $condition) {
        $group->condition($condition);
      }
      $query->condition($group);
      $query->execute();
    }
  }

  protected function deleteClaimedItems() {
    // @TODO: Add dependencies/expiry check for deleting
    $this->database->delete('log_monitor_log')
      ->condition('status', LogMonitorLog::STATUS_CLAIMED)
      ->execute();
  }

  protected function addClaimedItemDependencies() {
    $entities = \Drupal::entityTypeManager()->getStorage('log_monitor_rule')->loadByProperties(['status' => '1']);
    foreach($entities as $entity) {
      $query = $this->database->select('log_monitor_log', 'lml')
        ->condition('status', LogMonitorLog::STATUS_MATCHED)
        ->fields('lml', ['wid']);
      $query->condition($entity->queryConditionGroup($query));
      if($results = $query->execute()->fetchCol()) {
        foreach($results as $log_id) {
          $entity->addDependentLog($log_id);
        }
      }
    }
  }

  protected function completeMatchedItems() {
    //@todo: Add depedencies to all STATUS_MATCHED items.
    $this->database->update('log_monitor_log')
      ->fields(['status' => LogMonitorLog::STATUS_PROCESSED])
      ->condition('status', LogMonitorLog::STATUS_MATCHED)
      ->execute();
  }


  protected function getQueryConditions($query) {
    $entities = \Drupal::entityTypeManager()->getStorage('log_monitor_rule')->loadByProperties(['status' => '1']);
    $conditions = [];
    foreach($entities as $entity) {
      $conditions[] = $entity->queryConditionGroup($query);
    }
    return $conditions;
  }

}
