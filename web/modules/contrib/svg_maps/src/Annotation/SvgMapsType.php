<?php

namespace Drupal\svg_maps\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Svg maps plugin item annotation object.
 *
 * @see \Drupal\svg_maps\SvgMapsTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class SvgMapsType extends Plugin {


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

  /**
   * A brief description of the plugin.
   *
   * This will be shown when adding or configuring this display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
