<?php

namespace Drupal\bridtv;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

class BridEntityResolver {

  const ENTITY_TYPE = 'media';

  const BUNDLE = 'bridtv';

  const FIELD = 'field_bridtv';

  protected $cached;

  protected $cacheResetCounter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $entityMemoryCache;

  /**
   * BridEntityResolver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $entity_memory_cache
   *   The entity memory cache.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MemoryCacheInterface $entity_memory_cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityMemoryCache = $entity_memory_cache;
    $this->cached = ['video' => []];
    $this->cacheResetCounter = 0;
  }

  /**
   * Get the representing entity for the given video id.
   *
   * @param int $id
   *   The Brid.TV video id.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The entity if existent, or NULL if not existent.
   */
  public function getEntityForVideoId($id) {
    $storage = $this->getEntityStorage();
    if (!isset($this->cached['video'][$id])) {
      $this->usingInternalCache();
      $query = $storage->getQuery();
      $query->condition(static::FIELD . '.video_id', $id, '=');
      $query->range(0, 1);
      if ($result = $query->execute()) {
        $this->cached['video'][$id] = reset($result);
      }
    }
    return isset($this->cached['video'][$id]) ? $storage->load($this->cached['video'][$id]) : NULL;
  }

  /**
   * Creates a new entity instance.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   A new entity, unsaved, without any field values.
   */
  public function newEntity() {
    $storage = $this->getEntityStorage();
    $definition = $this->getEntityTypeDefinition();
    $bundle_key = $definition->getKey('bundle');
    return $storage->create([$bundle_key => static::BUNDLE, 'uid' => 1]);
  }

  public function getEntityTypeDefinition() {
    return $this->entityTypeManager->getDefinition(static::ENTITY_TYPE);
  }

  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  public function getEntityStorage() {
    return $this->entityTypeManager->getStorage(static::ENTITY_TYPE);
  }

  /**
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  public function getEntityQuery() {
    $query = $this->getEntityStorage()->getQuery();
    $query->condition($this->getEntityTypeDefinition()->getKey('bundle'), static::BUNDLE, '=');
    return $query;
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|null
   */
  public function getFieldItemList(FieldableEntityInterface $entity) {
    foreach ($entity->getFieldDefinitions() as $definition) {
      if ($definition->getType() === 'bridtv') {
        $field = $definition->getName();
        return $entity->get($field);
      }
    }
    return NULL;
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param bool $decode
   *
   * @return array|null
   */
  public function getVideoData(FieldableEntityInterface $entity, $decode = TRUE) {
    if ($items = $this->getFieldItemList($entity)) {
      if (!$items->isEmpty()) {
        /** @var \Drupal\bridtv\Plugin\Field\FieldType\BridtvItem $item */
        $item = $items->first();
        return $item->getBridApiData($decode);
      }
    }
    return NULL;
  }

  protected function usingInternalCache() {
    $this->cacheResetCounter++;
    if ($this->cacheResetCounter > 100) {
      $this->cacheResetCounter = 0;
      $this->cached = ['video' => []];
      $this->getEntityStorage()->resetCache();
      $this->entityMemoryCache->deleteAll();
    }
  }

}
