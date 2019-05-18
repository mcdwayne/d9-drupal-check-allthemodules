<?php

namespace Drupal\getresponse_forms;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable image effects.
 *
 * @see \Drupal\getresponse_forms\Annotation\Field
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see \Drupal\image\ImageEffectManager
 * @see plugin_api
 */
interface ConfigurableFieldInterface extends FieldInterface, PluginFormInterface {
}
