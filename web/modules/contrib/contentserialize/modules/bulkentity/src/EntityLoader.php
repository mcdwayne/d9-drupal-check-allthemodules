<?php

namespace Drupal\bulkentity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Loads entities in batches.
 *
 * Note this will bust the persistent entity cache as well as static.
 * @see https://www.drupal.org/project/drupal/issues/2577417#comment-10658038
 * @see https://www.drupal.org/project/drupal/issues/1596472
 *
 * @todo Update to use the new entity.memory_cache service once #1596472 lands.
 */
class EntityLoader implements EntityLoaderInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Create the entity loader.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function byIds($batch_size, array $ids, $entity_type_id) {
    foreach (array_chunk($ids, $batch_size) as $batch_ids) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entities = $storage->loadMultiple($batch_ids);
      foreach ($entities as $entity) {
        yield $entity;
      }
      $storage->resetCache($batch_ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function byQuery($batch_size, QueryInterface $query) {
    $ids = array_values($query->execute());
    return $this->byIds($batch_size, $ids, $query->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function byEntityType($batch_size, $entity_type_id, $bundles = []) {
    $query = \Drupal::entityQuery($entity_type_id);
    if ($bundles) {
      $definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $bundle_key = $definition->getKey('bundle');
      if ($bundle_key === FALSE) {
        throw new \InvalidArgumentException("Couldn't find bundle key for entity type $entity_type_id.");
      }
      $query->condition($bundle_key, $bundles, 'IN');
    }
    return $this->byQuery($batch_size, $query);
  }

}