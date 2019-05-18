<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Reference type entities.
 */
interface ReferenceTypeInterface extends ConfigEntityInterface {

  /**
   * Get description.
   *
   * @return string
   *   Short description.
   */
  public function getDescription();

  /**
   * Set description.
   *
   * @param string $desc
   *   String of description.
   *
   * @return \Drupal\bibcite_entity\Entity\ReferenceTypeInterface
   *   Callable entity object.
   */
  public function setDescription(string $desc);

  /**
   * Get fields configuration array.
   *
   * @return array
   *   Array of fields configuration.
   */
  public function getFields();

  /**
   * Set fields configuration array.
   *
   * @param array $fields
   *   Array of fields configuration.
   *
   * @return \Drupal\bibcite_entity\Entity\ReferenceTypeInterface
   *   Callable entity object.
   */
  public function setFields(array $fields);

  /**
   * Check if properties should be overridden for this type.
   *
   * @return bool
   *   TRUE if override is required, false otherwise.
   */
  public function isRequiredOverride();

}
