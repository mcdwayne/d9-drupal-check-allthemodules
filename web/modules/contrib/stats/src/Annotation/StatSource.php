<?php

namespace Drupal\stats\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Stat source item annotation object.
 *
 * @see \Drupal\stats\Plugin\StatSourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class StatSource extends Plugin {


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
