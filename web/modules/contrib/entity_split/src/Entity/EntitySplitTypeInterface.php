<?php

namespace Drupal\entity_split\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Entity split type entities.
 */
interface EntitySplitTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the master entity type.
   *
   * @return string
   *   The master entity type.
   */
  public function getMasterEntityType();

  /**
   * Sets the master entity type.
   *
   * @param string $entity_type
   *   The master entity type.
   *
   * @return $this
   */
  public function setMasterEntityType($entity_type);

  /**
   * Returns the master entity bundle.
   *
   * @return string
   *   The master entity bundle.
   */
  public function getMasterBundle();

  /**
   * Sets the master entity bundle.
   *
   * @param string $bundle
   *   The master entity bundle.
   *
   * @return $this
   */
  public function setMasterBundle($bundle);

  /**
   * Gets a list of entity split types for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\entity_split\Entity\EntitySplitTypeInterface[]
   *   An array of entity split types.
   */
  public static function getEntitySplitTypesForEntity(ContentEntityInterface $entity);

  /**
   * Gets a list of entity split types for the entity type.
   *
   * @param string $entity_type
   *   The entity.
   *
   * @return \Drupal\entity_split\Entity\EntitySplitTypeInterface[]
   *   An array of entity split types.
   */
  public static function getEntitySplitTypesForEntityType($entity_type);

  /**
   * Returns the translation support status for the bundle.
   *
   * @return bool
   *   TRUE if the bundle has translation support enabled.
   */
  public function isTranslatableBundle();

}
