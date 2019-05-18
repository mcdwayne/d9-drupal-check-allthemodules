<?php

namespace Drupal\cache_tools\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Sanitize cache tags.
 */
class CacheSanitizer {

  /**
   * The cache tag handler.
   *
   * @var \Drupal\cache_tools\Service\CacheInvalidator
   */
  protected $cacheInvalidator;

  /**
   * Cache tools settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * CacheSanitizer constructor.
   *
   * @param \Drupal\cache_tools\Service\CacheInvalidator $cache_invalidator
   *   PublishedEntityCacheTag object.
   * @param array $settings
   *   The cache tools settings.
   */
  public function __construct(CacheInvalidator $cache_invalidator, array $settings) {
    $this->cacheInvalidator = $cache_invalidator;
    $this->settings = $settings;
  }

  /**
   * Filter cacheable metadata based on given options.
   *
   * @param array $filterCacheableMetadata
   *   Cacheable metadata to filter out.
   * @param array $cacheableMetadata
   *   Cacheable metadata.
   * @param array $options
   *   Options to include/exclude cacheable metadata.
   *
   * @return array
   *   Filtered cacheable metadata.
   */
  protected function filterCacheableMetadata(array $filterCacheableMetadata, array $cacheableMetadata, array $options) {
    if (!empty($options['include'])) {
      $cacheableMetadata = array_merge($cacheableMetadata, $options['include']);
    }
    if (!empty($options['exclude'])) {
      $filterCacheableMetadata = array_merge($filterCacheableMetadata, $options['exclude']);
    }
    return array_diff($cacheableMetadata, $filterCacheableMetadata);
  }

  /**
   * Sanitizes cacheable contexts.
   *
   * @param array $cachebleContexts
   *   Cacheable contexts.
   * @param array $options
   *   Optional. List of cache contexts to include/exclude.
   *
   * @return array
   *   Sanitized cacheble contexts.
   */
  public function sanitizeCacheableContexts(array $cachebleContexts, array $options = []) {
    $defaults = $this->settings['sanitize']['defaults']['contexts'] ?? [];
    return $this->filterCacheableMetadata($defaults, $cachebleContexts, $options);
  }

  /**
   * Sanitizes cacheable tags.
   *
   * @param array $cachebleTags
   *   Cacheable contexts.
   * @param array $options
   *   Optional. List of cache tags to include/exclude.
   *
   * @return array
   *   Sanitized cacheble tags.
   */
  public function sanitizeCacheableTags(array $cachebleTags, array $options = []) {
    $defaults = $this->settings['sanitize']['defaults']['tags'] ?? [];
    return $this->filterCacheableMetadata($defaults, $cachebleTags, $options);
  }

  /**
   * Sanitizes cacheable metadata.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param array $build
   *   Build.
   */
  public function sanitize(EntityInterface $entity, array &$build) {
    // Skip if entity is not listed for sanitizing.
    $sanitize = array_key_exists($entity->id(), $this->settings['sanitize'][$entity->getEntityTypeId()]);
    if (!$sanitize) {
      return;
    }
    $cacheSettings = $this->settings['sanitize'][$entity->getEntityTypeId()][$entity->id()] ?? [];
    $this->sanitizeBuild($build, $cacheSettings);
  }

  /**
   * Sanitize build.
   *
   * @param array $build
   *   Build.
   * @param array $options
   *   Optional. Settings to adjust sanitizing.
   */
  public function sanitizeBuild(array &$build, array $options = []) {
    $contextsOptions = $options['contexts'] ?? [];
    $tagsOptions = $options['tags'] ?? [];
    // Filter out undesired cache contexts.
    if (!empty($build['#cache']['contexts'])) {
      $build['#cache']['contexts'] = $this->sanitizeCacheableContexts($build['#cache']['contexts'], $contextsOptions);
    }
    // Filter out undesired cache tags.
    if (!empty($build['#cache']['tags'])) {
      $build['#cache']['tags'] = $this->sanitizeCacheableTags($build['#cache']['tags'], $tagsOptions);
    }
    // Sanitize also build content.
    if (!empty($build['content']['#cache']['contexts'])) {
      $build['content']['#cache']['contexts'] = $this->sanitizeCacheableContexts($build['content']['#cache']['contexts'], $contextsOptions);
    }
    if (!empty($build['content']['#cache']['tags'])) {
      $build['content']['#cache']['tags'] = $this->sanitizeCacheableTags($build['content']['#cache']['tags'], $tagsOptions);
    }
  }

  /**
   * Get published cache tag in format `entitytype_entitybundle_pub`.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return string
   *   Published ache tag.
   *
   * @deprecated
   *   Please use Drupal\cache_tools\Service\PublishedEntityCacheTag::getPublishedEntityCacheTag()
   * instead.
   */
  public function getPublishedEntityCacheTag(EntityInterface $entity) {
    return $this->cacheInvalidator->getPublishedEntityCacheTag($entity);
  }

  /**
   * Get published cache tag in format `entitytype_pub`.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   Entity.
   *
   * @return string
   *   Published cache tag.
   *
   * @deprecated
   *   Please use Drupal\cache_tools\Service\PublishedEntityCacheTag::getPublishedEntityTypeCacheTag()
   * instead.
   */
  public function getPublishedEntityTypeCacheTag(EntityTypeInterface $entityType) {
    return $this->cacheInvalidator->getPublishedEntityTypeCacheTag($entityType);
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
   *
   * @deprecated
   *   Please use Drupal\cache_tools\Service\PublishedEntityCacheTag::invalidatePublishedEntity()
   * instead.
   */
  public function invalidatePublishedEntity(EntityInterface $entity) {
    return $this->cacheInvalidator->invalidatePublishedEntity($entity);
  }

}
