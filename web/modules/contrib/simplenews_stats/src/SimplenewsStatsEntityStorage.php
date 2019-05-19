<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\simplenews\SubscriberInterface;

/**
 * Simplenews stats entity storage.
 */
class SimplenewsStatsEntityStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {

    $storage_child = \Drupal::entityTypeManager()->getStorage('simplenews_stats_item');

    foreach ($entities as $entity) {

      /* @var $newsletter Drupal\core\Entity\EntityInterface */
      $childs_ids = \Drupal::entityQuery('simplenews_stats_item')
        ->condition('entity_type', $entity->entity_type->first()->getValue())
        ->condition('entity_id', $entity->entity_id->first()->getValue())
        ->execute();

      if (!empty($childs_ids)) {
        $childs = $storage_child->loadMultiple($childs_ids);
        \Drupal::entityTypeManager()->getStorage('simplenews_stats_item')->delete($childs);
      }
    }

    parent::delete($entities);
  }

  /**
   * Return the global newsletter stat from related entity (node).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity use as simplenews.
   *
   * @return \Drupal\simplenews_stats\Entity\SimplenewsStats
   *   The simplenews stats entity.
   */
  public function getFromRelatedEntity(EntityInterface $entity) {
    $result = \Drupal::entityQuery('simplenews_stats')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->execute();

    if (empty($result)) {
      return FALSE;
    }

    $id = reset($result);
    return \Drupal::entityTypeManager()->getStorage('simplenews_stats')->load($id);
  }

  /**
   * Create an entity from subscriber provide by simplenews and the related 
   * entity.
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity use as simplenews.
   * 
   * @return \Drupal\simplenews_stats\SimplenewsStatsInterface
   *   The simplenews stats entity.
   */
  public function createFromSubscriberAndEntity(SubscriberInterface $subscriber, EntityInterface $entity) {
    $data = [
      'snid'        => $subscriber->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id'   => $entity->id(),
      'created'     => \Drupal::time()->getRequestTime(),
    ];

    return $this->create($data);
  }

}
