<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/27/17
 * Time: 3:30 PM
 */

namespace Drupal\log_monitor\Plugin\QueueWorker;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\log_monitor\DependencyManager;

/**
 * @QueueWorker(
 *   id = "reaction_processor",
 *   title = @Translation("Reaction Processor"),
 *   cron = {"time" = 60}
 * )
 */
class ReactionProcessor extends QueueWorkerBase {


  /**
   * {@inheritdoc}
   */
  public function processItem($entity) {
    // Get all reactions for this entity and add them to the queue
    $reactions = $entity->getReactions();
    foreach ($reactions as $reaction) {
      $reaction->action($entity);
    }

    // Update the associated dependencies and add a timestamp
    $db = Database::getConnection();
    $db->update('log_monitor_log_dependencies')
      ->fields(['status' => DependencyManager::STATUS_HOLD])
      ->condition('status', DependencyManager::STATUS_INIT)
      ->condition('entity_id', $entity->id())
      ->execute();
    $now = new \DateTime('now');
    \Drupal::state()->set('log_monitor.' . $entity->id() . '.hold', $now);
  }

}
