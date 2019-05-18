<?php

namespace Drupal\global_gateway\Mapper;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface MapperInterface.
 */
interface MapperInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Retrieve plugin label.
   *
   * @return string
   *   Returns the plugin label.
   */
  public function getLabel();

  /**
   * Region setter.
   *
   * @param string $region
   *   The region code.
   *
   * @return $this
   */
  public function setRegion($region);

  /**
   * Retrieve entity.
   *
   * @return \Drupal\global_gateway\Entity\RegionMapping
   *   The mapping entity.
   */
  public function getEntity();

  /**
   * Creates config_entity of specific type.
   *
   * @param array $values
   *   The array of entity properties.
   *
   * @return \Drupal\global_gateway\Entity\RegionMapping
   *   A new mapping entity.
   */
  public function createEntity(array $values = []);

  /**
   * Returns operation links for config entity.
   *
   * @return array
   *   The render array with links.
   */
  public function getOperationsLinks();

  /**
   * Build overview data for specific region.
   *
   * @return array|string
   *   The render array or string with overview.
   */
  public function getOverviewByRegion();

}
