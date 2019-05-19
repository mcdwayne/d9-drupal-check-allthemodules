<?php

/**
 * @file
 * Definition of Drupal\visualization\Plugin\VisualizationHandlerManager
 */

namespace Drupal\visualization\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Plugin type manager for all views handlers.
 */
class VisualizationHandlerManager extends PluginManagerBase {

  /**
   * Constructs a ViewsHandlerManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct($type, \Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery("Plugin/visualization/$type", $namespaces);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
