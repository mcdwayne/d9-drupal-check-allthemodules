<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\content_entity_builder\BaseFieldConfigInterface;

/**
 * Provides an interface defining a content type entity .
 */
interface ContentTypeInterface extends ConfigEntityInterface {

  /**
   * Returns a specific base_field.
   *
   * @param string $base_field
   *   The base_field ID.
   *
   * @return \Drupal\content_entity_builder\BaseFieldConfigInterface
   *   The base_field object.
   */
  public function getBaseField($base_field);

  /**
   * Returns the base_fields for this content entity type.
   *
   * @return \Drupal\content_entity_builder\BaseFieldConfigPluginCollection|\Drupal\content_entity_builder\BaseFieldConfigInterface[]
   *   The base_field plugin collection.
   */
  public function getBaseFields();

  /**
   * Saves a base_field for this  content entity type.
   *
   * @param array $configuration
   *   An array of base_field configuration.
   *
   * @return string
   *   The base_field ID.
   */
  public function addBaseField(array $configuration);

  /**
   * Deletes a base_field from this content entity type.
   *
   * @param \Drupal\content_entity_builder\BaseFieldConfigInterface $base_field
   *   The base_field object.
   *
   * @return $this
   */
  public function deleteBaseField(BaseFieldConfigInterface $base_field);

  public function getEntityKeys();

  public function setEntityKeys(array $entity_keys);

  public function getEntityKey($key);

  public function getEntityPaths();

  public function setEntityPaths(array $entity_paths);

  public function getEntityPath($path);

  public function isApplied();

  public function setApplied($applied);

  public function hasData();

}
