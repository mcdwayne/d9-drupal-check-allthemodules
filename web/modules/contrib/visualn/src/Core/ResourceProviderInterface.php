<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for VisualN Resource Provider plugins.
 */
//interface VisualNResourceProviderInterface extends PluginInspectionInterface {
interface ResourceProviderInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get Resource object corresponding to the current provider.
   *
   * @return \Drupal\visualn\Core\VisualNResourceInterface
   */
  public function getResource();

}
