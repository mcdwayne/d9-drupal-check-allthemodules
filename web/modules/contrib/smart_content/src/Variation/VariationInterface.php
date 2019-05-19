<?php

namespace Drupal\smart_content\Variation;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Smart variation plugins.
 */
interface VariationInterface extends PluginInspectionInterface {

  public function writeChangesToConfiguration();

  // Add get/set methods for your plugin type here.

}
