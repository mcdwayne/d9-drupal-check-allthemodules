<?php

namespace Drupal\entity_library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a entity_library entity.
 */
interface EntityLibraryInterface extends ConfigEntityInterface {

  /**
   * Sets the label for the library.
   *
   * @param string $label
   *   The label for the library.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the description for the library.
   *
   * @return string
   *   The description for the library.
   */
  public function getDescription();

  /**
   * Sets the description for the library.
   *
   * @param string $description
   *   The description for the library.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the configuration for the library.
   *
   * @return array
   *   The configuration for the library.
   */
  public function getLibraryInfo();

  /**
   * Sets the library definition.
   *
   * @param string $library_info
   *   The definition for the library.
   *
   * @return $this
   */
  public function setLibraryInfo(string $library_info);

  /**
   * Returns the conditions for the library.
   *
   * @return array
   *   The conditions for the library.
   */
  public function getConditions();

  /**
   * Sets the conditions for the library.
   *
   * @param array $conditions
   *   The conditions for the library.
   *
   * @return $this
   */
  public function setConditions(array $conditions);

  /**
   * Clears static and persistent library definition caches.
   */
  public static function clearCachedLibraryDefinitions();

}
