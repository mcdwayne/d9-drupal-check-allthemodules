<?php

namespace Drupal\svg_maps\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining Svg maps entity entities.
 */
interface SvgMapsEntityInterface extends EntityWithPluginCollectionInterface, ConfigEntityInterface {

  /**
   * Returns the svg maps type plugin.
   *
   * @return \Drupal\svg_maps\SvgMapsTypeInterface
   *   The type.
   */
  public function getType();

  /**
   * Gets the Svg maps path.
   *
   * @return array
   *   Path of the Svg maps.
   */
  public function getMapsPath();

  /**
   * Sets the Svg maps path.
   *
   * @param array $path
   *   The Svg maps path.
   *
   * @return \Drupal\svg_maps\Entity\SvgMapsEntityInterface
   *   The called Svg maps entity.
   */
  public function setMapsPath(array $path);

  /**
   * Returns the api type configuration.
   *
   * @return array
   *   The type configuration.
   */
  public function getTypeConfiguration();

  /**
   * Sets the api type configuration.
   *
   * @param array $configuration
   *   The type configuration.
   */
  public function setTypeConfiguration($configuration);
}
