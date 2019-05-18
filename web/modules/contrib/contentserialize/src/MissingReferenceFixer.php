<?php

namespace Drupal\contentserialize;

use Drupal\bulkentity\EntityLoaderInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Tracks references between entities that can't be set on import.
 *
 * It's possible that an entity is imported before all its dependencies (eg. if
 * there's a cyclic refrence it can't be avoided). This object will track which
 * entities are missing references to which others and will call a custom
 * callback when the import's finished.
 *
 * @todo Add tests.
 */
class MissingReferenceFixer {

  /**
   * The batch size to use when loading referenced entities.
   *
   * @var int
   */
  const BATCH_SIZE = 50;

  /**
   * @var array
   */
  protected $targets = [];

  /**
   * Callbacks to fix missing entity references keyed by entity type and UUID.
   *
   * @var array
   */
  protected $callbacks = [];

  /**
   * The entity loader
   *
   * @var \Drupal\bulkentity\EntityLoaderInterface
   */
  protected $entityLoader;

  /**
   * Creates a new missing reference fixer.
   *
   * @param \Drupal\bulkentity\EntityLoaderInterface $entity_loader
   *   The bulk entity loader.
   */
  public function __construct(EntityLoaderInterface $entity_loader) {
    $this->entityLoader = $entity_loader;
  }

  /**
   * Register that an imported entity has a reference to a non-existent entity.
   *
   * @param string $type
   *   The entity type ID of the referencing entity;
   * @param string $uuid
   *   The UUID of the referencing entity;
   * @param string $target_type
   *   The entity type ID of the missing referenced entity;
   * @param $target_uuid
   *   The UUId of the missing referenced entity;
   * @param callable $callback
   *   A callback that will fix the missing dependency; it takes three
   *   arguments:
   *   - the loaded referencing entity object;
   *   - the entity ID of the referenced entity;
   *   - the revision ID of the referenced entity (may be NULL).
   */
  public function register($type, $uuid, $target_type, $target_uuid, callable $callback) {
    $this->callbacks[$type][$uuid][] = [$callback, $target_type, $target_uuid];
    $this->targets[$target_type][] = $target_uuid;
  }

  /**
   * Fix all registered missing references.
   */
  public function fix() {
    $map = [];
    $vids = [];
    foreach ($this->loadReferencedEntities() as $entity) {
      $map[$entity->uuid()] = $entity->id();

      // @todo Remove the special casing of entity reference revisions once
      //   #2667748 lands.
      $vid = $entity->getRevisionId();
      if ($vid) {
        $vids[$entity->uuid()] = $vid;
      }
    }

    // @todo Verify that the referenced entity types match.
    foreach ($this->loadReferencingEntities() as $entity) {
      try {
        foreach ($this->callbacks[$entity->getEntityTypeId()][$entity->uuid()] as $callback_data) {
          list($callback, $target_type, $target_uuid) = $callback_data;
          $target_id = $map[$target_uuid];
          $target_vid = !empty($vids[$target_uuid]) ? $vids[$target_uuid] : NULL;
          $callback($entity, $target_id, $target_vid);
        }

        $entity->save();
      }
      catch (\Exception $e) {
        watchdog_exception('contentserialize', $e);
      }
    }
  }

  /**
   * Batch load referencing entities.
   *
   * @return \Generator|\Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected function loadReferencingEntities() {
    // Although you can query by UUID with an entity query, the results won't
    // map the serial ID to UUID so we need to load the entities.
    foreach ($this->callbacks as $entity_type_id => $entities_data) {
      $query = \Drupal::entityQuery($entity_type_id)
        ->condition('uuid', array_keys($entities_data), 'IN');
      // @todo: PHP 7.x: Use yield from.
      foreach ($this->entityLoader->byQuery( static::BATCH_SIZE, $query) as $entity) {
        yield $entity;
      }
    }
  }

  /**
   * Batch load referenced entities.
   *
   * @return \Generator|\Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected function loadReferencedEntities() {
    foreach ($this->targets as $target_type => $uuids) {
      $query = \Drupal::entityQuery($target_type)
        ->condition('uuid', $uuids, 'IN');
      // @todo: PHP 7.x: Use yield from.
      foreach ($this->entityLoader->byQuery( static::BATCH_SIZE, $query) as $entity) {
        yield $entity;
      }
    }
  }

  /**
   * Get the callbacks to run for a referencing entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The referencing entity
   *
   * @return \Generator
   */
  protected function getCallbacks(ContentEntityInterface $entity) {
    foreach ($this->callbacks[$entity->getEntityTypeId()][$entity->uuid()] as $callback_data) {
      yield $callback_data;
    }
  }

}