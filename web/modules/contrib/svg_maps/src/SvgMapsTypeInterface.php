<?php

namespace Drupal\svg_maps;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\svg_maps\Entity\SvgMapsEntityInterface;

/**
 * Defines an interface for Svg maps plugin plugins.
 */
interface SvgMapsTypeInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get the global theme used for rendering in formatter
   *
   * @return string
   *   The theme to use.
   */
  public function getGlobalTheme();

  /**
   * Get the detailed theme used for rendering in formatter
   *
   * @return string
   *   The theme to use.
   */
  public function getDetailedTheme();

}
