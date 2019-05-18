<?php

namespace Drupal\handy_cache_tags;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Handy handler.
 */
class HandyCacheTagsHandler {

  /**
   * Handy cache tags manager service.
   *
   * @var \Drupal\handy_cache_tags\HandyCacheTagsManager
   */
  protected $manager;

  /**
   * HandyCacheTagsHandler constructor.
   */
  public function __construct(HandyCacheTagsManager $manager) {
    $this->manager = $manager;
  }

  /**
   * Invalidate caches for an entity.
   */
  public function invalidateEntity(EntityInterface $entity) {
    Cache::invalidateTags($this->manager->getEntityTags($entity));
    // If the entity is a bundle config entity, we want to clear the cache for
    // the potential entities it affects.
    if ($entity instanceof ConfigEntityBundleBase) {
      // Try to get the entity type.
      $config_entity_type = $entity->getEntityType();
      $bundle_entity_id = $config_entity_type->getBundleOf();
      $bundle = $entity->id();
      Cache::invalidateTags([
        // Clear cache for all combinations of entity and bundle. Like
        // node->article.
        $this->manager->getBundleTag($bundle_entity_id, $bundle),
        // Clear cache for all entities as well.
        // @todo: Figure out why we do this? I do not remember at this point,
        // and if there is a reason, write an integration test for it.
        // @see https://www.drupal.org/project/handy_cache_tags/issues/2945321
        $this->manager->getTag($bundle_entity_id),
      ]);
    }
    // If the entity is a field config, we want to clear the cache for the
    // potential entities it affects.
    if ($entity instanceof FieldStorageConfig) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $field_config */
      $field_storage_config = $entity;
      Cache::invalidateTags(([
        // Clear the cache of the entity type (like node) the field belongs to,
        // since the storage config have no idea what kind of entities might use
        // it.
        $this->manager->getTag($field_storage_config->getTargetEntityTypeId()),
      ]));
    }
    if ($entity instanceof FieldConfig) {
      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      $field_config = $entity;
      $entity_type = $field_config->getTargetEntityTypeId();
      Cache::invalidateTags([
        // Clear the cache for the bundle this field belongs to.
        $this->manager->getBundleTag($field_config->getTargetBundle(), $entity_type),
        // Clear cache for all entities as well.
        // @todo: Figure out why we do this? I do not remember at this point,
        // and if there is a reason, write an integration test for it.
        // @see https://www.drupal.org/project/handy_cache_tags/issues/2945321
        $this->manager->getTag($entity_type),
      ]);
    }
  }

}
