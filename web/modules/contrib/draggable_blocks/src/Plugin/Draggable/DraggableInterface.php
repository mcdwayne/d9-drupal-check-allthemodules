<?php

/**
 * @file
 * Contains \Drupal\draggable_blocks\Plugin\DraggablePluginInterface.
 */

namespace Drupal\draggable_blocks\Plugin\Draggable;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for static Draggable plugins.
 */
interface DraggableInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

}
