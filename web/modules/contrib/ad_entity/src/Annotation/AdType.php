<?php

namespace Drupal\ad_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation for Advertising type plugins.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AdType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Advertising type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
