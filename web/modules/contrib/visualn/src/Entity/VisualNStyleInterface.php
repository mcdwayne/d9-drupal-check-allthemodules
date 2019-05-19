<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining VisualN style entities.
 */
interface VisualNStyleInterface extends ConfigEntityInterface {

  /**
   * Base Drawer prefix.
   */
  const BASE_DRAWER_PREFIX = 'base';

  /**
   * User-defined (subdrawer) prefix.
   */
  const SUB_DRAWER_PREFIX = 'sub';

  /**
   * Get VisualN style drawer Id.
   *
   * @return string $drawer_id
   */
  public function getDrawerId();

  /**
   * Get VisualN style drawer configuration.
   *
   * @return array $drawer_config
   */
  public function getDrawerConfig();

  /**
   * Set drawer plugin configuration for VisualN style.
   *
   * @param array $drawer_config
   *
   * @return $this
   */
  public function setDrawerConfig($drawer_config);

  /**
   * Get VisualN style specific drawer plugin instance.
   *
   * @return \Drupal\visualn\Core\DrawerInterface
   */
  public function getDrawerPlugin();

  /**
   * Get VisualN style specific drawer type. Can be base drawer or a subdrawer.
   *
   * Base drawers are generic Drawer plugins.
   * Subdrawers are configuration VisualNDrawer entities. Subdrawers are based on
   * base drawers.
   */
  public function getDrawerType();

}
