<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Smart variation item annotation object.
 *
 * @see \Drupal\smart_content\Plugin\SmartVariationManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartVariationSetType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
