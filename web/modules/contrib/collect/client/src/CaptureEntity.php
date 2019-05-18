<?php

/**
 * @file
 * Contains \Drupal\collect_client\CaptureEntity.
 */

namespace Drupal\collect_client;

use Drupal\collect_client\Plugin\QueueWorker\CollectClientQueueWorker;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Captures an entity, creates an item and sends it to the server.
 */
class CaptureEntity {

  /**
   * Captures an existing entity as a collect container.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to capture.
   * @param string $operation
   *   (optional) The entity operation that triggered the capturing.
   */
  public function capture(ContentEntityInterface $entity, $operation = '') {
    $entities[$entity->getEntityTypeId() . ':' . $entity->id()] = $entity;
    collect_common_get_referenced_entities($entities, $entity, \Drupal::config('collect_client.settings')->get('entity_capture'));

    // Enqueue the references first.
    foreach (array_reverse($entities) as $captured_entity) {
      // Add entity to the queue if it is not already sent.
      if (!$this->isSent($captured_entity)) {
        $this->enqueueItem($captured_entity, $operation);
      }
    }
  }

  /**
   * Adds a item object to the queue.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to capture.
   * @param string $operation
   *   (optional) The entity operation that triggered the capturing.
   */
  protected function enqueueItem(ContentEntityInterface $entity, $operation = '') {
    $item = array(
      'date' => REQUEST_TIME,
      'entity' => $entity,
      'operation' => $operation,
      'cache_key' => collect_common_cache_key($entity),
    );
    \Drupal::queue(CollectClientQueueWorker::QUEUE_NAME)->createItem($item);
  }

  /**
   * Checks whether given entity is already sent to the Collect server.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The given content entity.
   *
   * @return bool
   *   Returns TRUE if given entity is sent, otherwise FALSE.
   */
  protected function isSent(ContentEntityInterface $entity) {
    // Mark entities as not sent in case they do not implement
    // EntityChangedInterface.
    if (!$entity instanceof EntityChangedInterface) {
      return FALSE;
    }
    // Get the cache key for given entity.
    $entity_cache_key = collect_common_cache_key($entity);
    $cached = \Drupal::cache()->get($entity_cache_key);
    $data['changed'] = $entity->getChangedTime();
    // Set a cache value (time of the change) for new entities or update the
    // cache value for entities that are changed.
    if ($cached) {
      if (isset($cached->data['changed']) && $data['changed'] == $cached->data['changed']) {
        return TRUE;
      }
      $data = array_merge($data, $cached->data);
    }
    \Drupal::cache()->set($entity_cache_key, $data);

    return FALSE;
  }

}
