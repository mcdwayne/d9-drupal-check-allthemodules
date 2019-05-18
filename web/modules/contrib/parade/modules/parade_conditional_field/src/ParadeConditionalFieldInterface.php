<?php

namespace Drupal\parade_conditional_field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Parade conditional field entities.
 */
interface ParadeConditionalFieldInterface extends ConfigEntityInterface {

  /**
   * Generate numeric ID from id.
   *
   * @return int
   *   Number.
   */
  public function getNumericId();

  /**
   * Returns Paragraphs bundle machine names.
   *
   * @return string
   *   Paragraphs bundle machine name.
   */
  public function getBundle();

  /**
   * Returns array of Classy paragraphs style machine names.
   *
   * @return array
   *   Classy paragraphs style machine names.
   */
  public function getLayouts();

  /**
   * Returns a View mode machine name.
   *
   * @return string
   *   View mode machine name.
   */
  public function getViewMode();

  /**
   * Returns array of Classy paragraphs style machine names..
   *
   * @return array
   *   Classy paragraphs style machine names.
   */
  public function getClasses();

  /**
   * Load conditions and return in custom array structure.
   *
   * @param array $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return array
   *   An array of conditions in custom condition structure.
   */
  public static function loadConditionsMultiple(array $ids = NULL);

}
