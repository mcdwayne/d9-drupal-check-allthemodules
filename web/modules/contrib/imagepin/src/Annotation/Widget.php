<?php

namespace Drupal\imagepin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a widget annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class Widget extends Plugin {

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
