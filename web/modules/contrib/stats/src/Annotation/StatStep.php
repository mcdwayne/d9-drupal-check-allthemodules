<?php

namespace Drupal\stats\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Stat process item annotation object.
 *
 * @see \Drupal\stats\Plugin\StatStepManager
 * @see plugin_api
 *
 * @Annotation
 */
class StatStep extends Plugin {

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
