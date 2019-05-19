<?php

namespace Drupal\visualn;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierInterface;

/**
 * Defines the interface for configurable drawer modifiers.
 *
 * @see \Drupal\visualn\Annotation\VisualNDrawerModifier
 * @see \Drupal\visualn\ConfigurableDrawerModifierBase
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierInterface
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierBase
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierManager
 * @see plugin_api
 */
interface ConfigurableDrawerModifierInterface extends VisualNDrawerModifierInterface, PluginFormInterface {
  // @todo: move the interface to the Plugins subdirectory and maybe rename to correspond other class and interface names
}

