<?php

namespace Drupal\loading_bar_library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a loading_bar_style entity.
 */
interface LoadingBarStyleInterface extends ConfigEntityInterface {

  /**
   * Sets the label for the loading bar style.
   *
   * @param string $label
   *   The label for the loading bar style.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the configuration for the loading bar style.
   *
   * @return array
   *   The configuration for the loading bar style.
   */
  public function getConfiguration();

  /**
   * Sets the configuration for the loading bar style.
   *
   * @param array $configuration
   *   The configuration for the loading bar style.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration);

  /**
   * Returns the description for the loading bar style.
   *
   * @return string
   *   The description for the loading bar style.
   */
  public function getDescription();

  /**
   * Sets the description for the loading bar style.
   *
   * @param string $description
   *   The description for the loading bar style.
   *
   * @return $this
   */
  public function setDescription($description);

}
