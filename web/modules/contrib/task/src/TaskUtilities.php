<?php

namespace Drupal\task;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\task\Entity\TaskStatus;
use Drupal\task\Entity\TaskInterface;

class TaskUtilities {

  public static function getAllTaskStatuses() {
    $entitites = \Drupal::entityTypeManager()->getStorage('task_status')->getQuery()->execute();
    // There is always a "closed" status.
    $statuses = ['closed' => 'Closed'];
    foreach ($entitites as $entity) {
      $ent = TaskStatus::load($entity);
      $statuses[$ent->id()] = $ent->label();
    }
    ksort($statuses);
    return $statuses;
  }

  /**
   * TODO update default status logic
   * @param $task_data
   */
  public static function createTask($task_data) {
    if (isset($task_data['type'])) {
      $type = $task_data['type'];
      $plugin_manager = \Drupal::service('plugin.manager.task_bundle');
      $plugin_definitions = $plugin_manager->getDefinitions();
      foreach ($plugin_definitions as $pd) {
        if (!empty($pd['bundle']) && $pd['bundle'] === $type) {
          $task_data['status'] = isset($task_data['status']) ?  $task_data['status'] : 'active';
          $pd['class']::createTask($task_data);
        }
      }
    }
  }

  /**
   * TODO update default status logic
   * @param $task_data
   */
  public static function getTaskOptions(TaskInterface $task) {
    $type = $task->bundle();
    $plugin_manager = \Drupal::service('plugin.manager.task_bundle');
    $plugin_definitions = $plugin_manager->getDefinitions();
    foreach ($plugin_definitions as $pd) {
      if (!empty($pd['bundle']) && $pd['bundle'] === $type) {
        return $pd['class']::getTaskOptions($task);
      }
    }
  }

}
