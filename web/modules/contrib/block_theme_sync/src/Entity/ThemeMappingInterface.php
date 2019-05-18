<?php

namespace Drupal\block_theme_sync\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining theme mapping entities.
 */
interface ThemeMappingInterface extends ConfigEntityInterface {

  /**
   * Returns the source theme.
   *
   * @return string
   *   The name of the source theme.
   */
  public function getSource();

  /**
   * Sets the name of the source theme.
   *
   * @param string $source
   *   The name of the source theme.
   *
   * @return \Drupal\block_theme_sync\Entity\ThemeMappingInterface
   *   The class instance this method is called on.
   */
  public function setSource($source);

  /**
   * Returns the destination theme.
   *
   * @return string
   *   The name of the destination theme.
   */
  public function getDestination();

  /**
   * Sets the name of the destination theme.
   *
   * @param string $destination
   *   The name of the destination theme.
   *
   * @return \Drupal\block_theme_sync\Entity\ThemeMappingInterface
   *   The class instance this method is called on.
   */
  public function setDestination($destination);

  /**
   * Returns a region mapping between source and destination theme.
   *
   * @return array
   *   An array of array elements with the following keys:
   *   - source: a source theme region
   *   - destination: a destination theme region
   */
  public function getRegionMapping();

  /**
   * Sets the region mapping between source and destination theme.
   *
   * @param array $region_mapping
   *   An array of array elements with the following keys:
   *   - source: a source theme region
   *   - destination: a destination theme region
   *
   * @return \Drupal\block_theme_sync\Entity\ThemeMappingInterface
   *   The class instance this method is called on.
   */
  public function setRegionMapping(array $region_mapping);

}
