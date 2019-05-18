<?php

namespace Drupal\pluggable;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for plugins.
 */
interface PluggablePluginInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Gets the plugin display label.
   * It shown in UI when manipulating a plugin.
   *
   * @return string
   *   The plugin display label.
   */
  public function getDisplayLabel();

}
