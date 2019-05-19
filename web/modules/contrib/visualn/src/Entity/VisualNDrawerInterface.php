<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining VisualN Drawer entities.
 */
interface VisualNDrawerInterface extends ConfigEntityInterface {

  /**
   * Get VisualN user drawer Base drawer Id.
   *
   * @return string $drawer_id
   */
  public function getBaseDrawerId();

  /**
   * Get VisualN user drawer Base drawer configuration.
   *
   * @return array $drawer_config
   */
  public function getDrawerConfig();

  /**
   * Set base drawer plugin configuration for VisualN user drawer.
   *
   * @param array $drawer_config
   *
   * @return $this
   */
  public function setDrawerConfig($drawer_config);

}
