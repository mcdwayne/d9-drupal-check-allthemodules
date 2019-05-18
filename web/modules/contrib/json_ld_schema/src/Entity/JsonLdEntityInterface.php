<?php

namespace Drupal\json_ld_schema\Entity;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Spatie\SchemaOrg\Type;

/**
 * An interface for JSON LD entity sources.
 */
interface JsonLdEntityInterface extends PluginInspectionInterface {

  /**
   * Check if a given entity type and view mode is applicable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $view_mode
   *   The view mode.
   *
   * @return bool
   *   if the given entity is applicable or not.
   */
  public function isApplicable(EntityInterface $entity, $view_mode);

  /**
   * Get the cacheable metadata associated with the current data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get data for.
   * @param string $view_mode
   *   The view mode the data will be attached to.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cacheable metadata object.
   */
  public function getCacheableMetadata(EntityInterface $entity, $view_mode) : CacheableMetadata;

  /**
   * Get data provided by this plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get data for.
   * @param string $view_mode
   *   The view mode the data will be attached to.
   *
   * @return \Spatie\SchemaOrg\Type
   *   Some schema data.
   */
  public function getData(EntityInterface $entity, $view_mode) : Type;

}
