<?php

namespace Drupal\webform_composite;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Composite entity.
 */
interface WebformCompositeInterface extends ConfigEntityInterface {

  /**
   * Get elements (YAML) value.
   *
   * @return string
   *   The elements raw value.
   */
  public function getElementsRaw();

  /**
   * Get composite elements decoded as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE for invalid element YAML.
   */
  public function getElementsDecoded();

  /**
   * Get administrative description.
   *
   * @return string
   *   HTML Administrative description text.
   */
  public function getDescription();

}
