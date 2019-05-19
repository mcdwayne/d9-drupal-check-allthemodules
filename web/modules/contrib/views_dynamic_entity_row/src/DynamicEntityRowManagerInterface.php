<?php

namespace Drupal\views_dynamic_entity_row;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines methods to manage Views Dynamic Entity Row configs.
 */
interface DynamicEntityRowManagerInterface {

  /**
   * Bundle control mode settings - Enable per bundle configuration.
   */
  const PER_BUNDLE = 0;

  /**
   * Bundle control mode settings - Enable support for all bundles.
   */
  const ALL_BUNDLES = 1;

  /**
   * Check Views Dynamic Entity Row support for entity type or bundle.
   *
   * @param string $entity_type_id
   *   Entity type machine name.
   * @param string|null $bundle
   *   Bundle name.
   *
   * @return bool
   *   Views Dynamic Entity Row supported.
   */
  public function isSupported($entity_type_id, $bundle = NULL);

  /**
   * Get bundle support mode.
   *
   * @param string $entity_type_id
   *   Entity type machine name.
   *
   * @return int
   *   Bundle control mode.
   */
  public function getSupportMode($entity_type_id);

  /**
   * Get supported bundles of entity type.
   *
   * @param string $entity_type_id
   *   Entity type machine name.
   *
   * @return array
   *   Bundles machine names list.
   */
  public function getSupportedBundles($entity_type_id);

  /**
   * Get dynamic View Mode of particular entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Selected entity.
   *
   * @return string|null
   *   Machine name of View Mode.
   */
  public function getDynamicViewMode(EntityInterface $entity);

  /**
   * Set dynamic View Mode of particular entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Selected entity.
   * @param string $view_mode
   *   View mode to set.
   *
   * @return $this
   */
  public function setDynamicViewMode(EntityInterface $entity, $view_mode);

  /**
   * Set dynamic View Mode of particular entity loaded by entity type and id.
   *
   * @param string $entity_type_id
   *   Entity type machine name.
   * @param int $entity_id
   *   Entity id.
   * @param string $view_mode
   *   View mode to set.
   *
   * @return $this
   */
  public function setDynamicViewModeByEntityId($entity_type_id, $entity_id, $view_mode);

  /**
   * Drop dynamic View Mode settings for particular entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Selected entity.
   *
   * @return $this
   */
  public function dropDynamicViewMode(EntityInterface $entity);

}
