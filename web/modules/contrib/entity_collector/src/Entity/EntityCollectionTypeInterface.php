<?php

namespace Drupal\entity_collector\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Entity collection type entities.
 */
interface EntityCollectionTypeInterface extends ConfigEntityInterface {

  /**
   * Get the value of the source from the entity.
   *
   * @return string
   *  Contains the machine name of an entity type.
   */
  public function getSource();

  /**
   * Set the source of the entity.
   *
   * @param string $source
   *  Contains the machine name of an entity type.
   *
   * @return mixed
   */
  public function setSource($source);

  /**
   * Get the source field name.
   *
   * @return string
   */
  public function getSourceFieldName();

  /**
   * Set the source field name.
   *
   * @param string $source_field_name
   *
   * @return string
   */
  public function setSourceFieldName($source_field_name);

}
