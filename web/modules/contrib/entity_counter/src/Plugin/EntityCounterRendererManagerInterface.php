<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Collects available entity counter renders.
 */
interface EntityCounterRendererManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Gets sorted plugin definitions.
   *
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to sort. If omitted, all plugin
   *   definitions are used.
   *
   * @return array[]
   *   An array of plugin definitions, sorted by label.
   */
  public function getSortedDefinitions(array $definitions = NULL);

}
