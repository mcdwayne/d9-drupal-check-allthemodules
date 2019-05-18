<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides an interface for interacting with an entity using a bundle plugin.
 */
interface BundlePluginEntityInterface {

  /**
   * Gets the bundle plugin Entity type.
   *
   * @return \Drupal\entity\BundlePlugin\BundlePluginInterface
   *   The bundle plugin entity type.
   */
  public function getType();

}
