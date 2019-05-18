<?php

namespace Drupal\cache_tools\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * For invalidating cache_tools cache tags for entity create, update, delete.
 *
 * @see cache_tools.module for usage.
 */
class CacheInvalidator {

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Cache tools settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * CacheSanitizer constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tag invalidator.
   * @param array $settings
   *   The cache settings.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator, array $settings) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->settings = $settings;
  }

  /**
   * Get published cache tag in format `entitytype_pub`.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   Entity.
   *
   * @return string
   *   Published cache tag.
   */
  public function getPublishedEntityTypeCacheTag(EntityTypeInterface $entityType) {
    return $entityType->id() . '_pub';
  }

  /**
   * Get published cache tag in format `entitytype_entitybundle_pub`.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return string
   *   Published ache tag.
   */
  public function getPublishedEntityCacheTag(EntityInterface $entity) {
    return $entity->getEntityTypeId() . '_' . $entity->bundle() . '_pub';
  }

  /**
   * Invalidates published entity.
   *
   * On entity update where original entity is unpublished and
   * going to be published and on entity insert where new entity is published.
   * Other cases are already covered by other tags:
   * 1. Unpublished entities (created or updated) does not affect anything.
   * 2. Published entities which stay published are invalidated via
   *    entity_type:id (eg node:123).
   * 3. Published entities going to be deleted or unpublished are invalidated
   *    via entity_type:id (eg node:123).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return bool
   *   True if successful invalidation. False otherwise.
   */
  public function invalidatePublishedEntity(EntityInterface $entity) {
    // Skip if entity type is not allowed by entity type and bundle.
    if (!isset($this->settings['invalidate'][$entity->getEntityTypeId()])) {
      return FALSE;
    }
    if (!in_array($entity->bundle(), $this->settings['invalidate'][$entity->getEntityTypeId()])) {
      return FALSE;
    }
    $tags = [
      $this->getPublishedEntityTypeCacheTag($entity->getEntityType()),
      $this->getPublishedEntityCacheTag($entity),
    ];
    // If this is update we need to take a look at original entity as well.
    $entities = [$entity];
    if ($entity->original) {
      $entities[] = $entity->original;
    }
    // If entity is going to be unpublished (insert)
    // or stays unpublished (update), skip.
    foreach ($entities as $index => $entity) {
      if (empty($entity->get('status')->value)) {
        unset($entities[$index]);
      }
    }
    if ($entities) {
      $this->cacheTagsInvalidator->invalidateTags($tags);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get field cache tags for configured fields having (modified) values.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity_compare
   *   (optional) An entity to compare field values with. When provided only
   *   non-equal field values will be considered.
   *
   * @return string[]
   *   The custom cache tags `entitytype_entitybundle_pub:field_name:value`.
   */
  public function getPublishedEntityFieldsCacheTags(FieldableEntityInterface $entity, FieldableEntityInterface $entity_compare = NULL) {
    $tags = [];
    $entity_type = $entity->getEntityTypeId();
    // Get field-based tags configured for current entity bundle.
    $bundle = $entity->bundle();
    $tag_prefix = $this->getPublishedEntityCacheTag($entity) . ':';
    foreach ($this->settings['invalidate'][$entity_type] as $cache_parameter) {
      $parts = explode(':', $cache_parameter);
      if (count($parts) != 2 || $parts[0] != $bundle) {
        // This setting is not for the current bundle or not field-based.
        continue;
      }
      $field_name = $parts[1];
      if ($entity->hasField($field_name)) {
        // The name of the value property, e.g. 'value' or 'target_id'.
        $key = $entity
          ->getFieldDefinition($field_name)
          ->getFieldStorageDefinition()
          ->getMainPropertyName();
        if (is_null($key)) {
          // The field has no main value property.
          continue;
        }
        $tag_prefix_field = $tag_prefix . $field_name . ':';
        if (isset($entity_compare)) {
          if ($entity->get($field_name)->getValue() === $entity_compare->get($field_name)->getValue()) {
            // Skip unmodified field.
            continue;
          }
          if (!$entity_compare->get($field_name)->isEmpty()) {
            // Add tag for the original field value.
            /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field_items */
            foreach ($entity_compare->get($field_name)->getValue() as $value) {
              $tags[] = $tag_prefix_field . $value[$key];
            }
          }
        }
        if (!$entity->get($field_name)->isEmpty()) {
          // Add tag for the new field value.
          /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field_items */
          foreach ($entity->get($field_name)->getValue() as $value) {
            $tags[] = $tag_prefix_field . $value[$key];
          }
        }
      }
    }

    return $tags;
  }

  /**
   * Invalidates published entity field-based cache tags.
   *
   * Invalidates cache tags of the following format
   * `entitytype_entitybundle_pub:field_name:value` during:
   * 1. Insert published: all non-empty field values.
   * 2. Delete published: all non-empty field values.
   * 3. Update published: only the modified field values.
   * 4. Update and publish: all non-empty field values.
   * 5. Update and unpublish: all non-empty original field values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return bool
   *   True if successful invalidation. False otherwise.
   */
  public function invalidatePublishedEntityFields(EntityInterface $entity) {
    // Skip if entity type is not fieldable or not configured.
    if (!$entity instanceof FieldableEntityInterface) {
      return FALSE;
    }
    $entity_type = $entity->getEntityTypeId();
    if (!isset($this->settings['invalidate'][$entity_type]) || !is_iterable($this->settings['invalidate'][$entity_type])) {
      return FALSE;
    }
    // Determine published status and assume the entity is published if it
    // doesn't implement the interface.
    $is_published = $entity instanceof EntityPublishedInterface ? $entity->isPublished() : TRUE;
    if (isset($entity->original)) {
      $was_published = $entity->original instanceof EntityPublishedInterface ? $entity->original->isPublished() : TRUE;
    }
    else {
      $was_published = FALSE;
    }
    // Get the cache tags depending on operation and published status.
    $tags = [];
    if ($is_published && $was_published) {
      // Update published: only the modified field values.
      $tags = $this->getPublishedEntityFieldsCacheTags($entity, $entity->original);
    }
    elseif ($was_published) {
      // Update and unpublish: all non-empty original field values.
      $tags = $this->getPublishedEntityFieldsCacheTags($entity->original);
    }
    elseif ($is_published) {
      // Insert or delete published, update and publish: all non-empty field
      // values.
      $entities[] = $entity;
      $tags = $this->getPublishedEntityFieldsCacheTags($entity);
    }
    if (!empty($tags)) {
      // Invalidate all the selected tags.
      $this->cacheTagsInvalidator->invalidateTags($tags);
      return TRUE;
    }
    return FALSE;
  }

}
