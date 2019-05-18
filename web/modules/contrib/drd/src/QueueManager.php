<?php

namespace Drupal\drd;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\drd\Entity\BaseInterface;
use Drupal\drd\Plugin\Action\Base as ActionBase;
use Drupal\drd\Plugin\Action\BaseEntityInterface;
use Drupal\drd\Plugin\Action\BaseGlobalInterface;

/**
 * Easy access to the DRD queue.
 */
class QueueManager {

  /**
   * Get the DRD Queue.
   *
   * @return \Drupal\advancedqueue\Entity\QueueInterface
   *   The DRD Queue.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getQueue() {
    $queue = Queue::load('drd');
    if (empty($queue)) {
      $queue = Queue::create([
        'id' => 'drd',
        'label' => 'DRD',
        'backend' => 'database',
      ]);
      $queue->save();
    }
    return $queue;
  }

  /**
   * Get the AdvancedQueue processor service.
   *
   * @return \Drupal\advancedqueue\ProcessorInterface
   *   The processor.
   */
  private function getProcessor() {
    return \Drupal::service('advancedqueue.processor');
  }

  /**
   * Process all jobs in the DRD queue.
   */
  public function processAll() {
    $this->getProcessor()->processQueue($this->getQueue());
  }

  /**
   * Get the number of items in the queue.
   *
   * @return int
   *   The number of items.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function countItems() {
    $counts = $this->getQueue()->getBackend()->countJobs();
    return $counts[Job::STATE_QUEUED];
  }

  /**
   * Add a new item to the queue.
   *
   * @param \Drupal\drd\Plugin\Action\Base $action
   *   The action.
   * @param \Drupal\drd\Entity\BaseInterface $entity
   *   The entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createItem(ActionBase $action, BaseInterface $entity = NULL) {
    $payload = [
      'action' => $action->getPluginId(),
      'arguments' => json_encode($action->getArguments()),
    ];
    if ($action instanceof BaseEntityInterface) {
      $type = 'drd_action_entity';
      $payload['entity_type'] = $entity->getEntityTypeId();
      $payload['entity_id'] = $entity->id();
    }
    elseif ($action instanceof BaseGlobalInterface) {
      $type = 'drd_action_global';
    }
    if (!empty($type)) {
      $job = Job::create($type, $payload);
      $this->getQueue()->enqueueJob($job);
      if (!$action->canBeQueued()) {
        $this->getProcessor()->processJob($job, $this->getQueue());
      }
    }
  }

  /**
   * Add new items to the queue.
   *
   * @param \Drupal\drd\Plugin\Action\Base $action
   *   The action.
   * @param \Drupal\drd\Entity\BaseInterface[] $entities
   *   The entities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createItems(ActionBase $action, array $entities) {
    foreach ($entities as $entity) {
      $this->createItem($action, $entity);
    }
  }

}
