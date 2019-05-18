<?php

namespace Drupal\entity_normalization;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides an interface for entity normalization managers.
 */
interface EntityNormalizationManagerInterface {

  /**
   * Is there is a configuration for the given entity and normalization format.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   *
   * @return bool
   *   Indication if there is a configuration available.
   */
  public function hasEntityConfig(FieldableEntityInterface $entity, $format = NULL);

  /**
   * Gets the configuration for a given entity and normalization format.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string|null $format
   *   Format the normalization result will be encoded as.
   *
   * @return \Drupal\entity_normalization\EntityConfigInterface|null
   *   The configuration or NULL when not found.
   */
  public function getEntityConfig(FieldableEntityInterface $entity, $format = NULL);

}
