<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\visualn\WindowParametersInterface;

/**
 * Defines an interface for VisualN Drawing Fetcher plugins.
 */
// @todo: there may be configurable fetcher plugins and not configurable, which don't need PluginFormInterface
//    as for DrawerModifier plugins
interface DrawingFetcherInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface, WindowParametersInterface {

  /**
   * Fetch drawing markup.
   */
  public function fetchDrawing();

}
