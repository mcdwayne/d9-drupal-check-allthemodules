<?php

namespace Drupal\loggable\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Loggable filter entities.
 */
interface LoggableFilterInterface extends ConfigEntityInterface {

  /**
   * Get the filter label.
   *
   * @return string
   *   The filter label.
   */
  public function getLabel();

  /**
   * Set the filter label.
   *
   * @param string $label
   *   The filter label.
   */
  public function setLabel(string $label);

  /**
   * Get the filter severity levels.
   *
   * @return array
   *   An array of severity levels.
   */
  public function getSeverityLevels();

  /**
   * Set the filter severity levels.
   *
   * @param array $levels
   *   An array of severity levels.
   */
  public function setSeverityLevels(array $levels);

  /**
   * Get the filter types.
   *
   * @return array
   *   An array of type patterns.
   */
  public function getTypes();

  /**
   * Set the filter types.
   *
   * @param array $types
   *   An array of type patterns.
   */
  public function setTypes(array $types);

  /**
   * Check if the filter is enabled.
   *
   * @return bool
   *   TRUE if the filter is enabled, otherwise FALSE.
   */
  public function isEnabled();

  /**
   * Set the filter as enabled.
   */
  public function setEnabled();

  /**
   * Set the filter as disabled.
   */
  public function setDisabled();

}
