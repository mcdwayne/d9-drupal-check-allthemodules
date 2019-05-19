<?php

namespace Drupal\taarikh\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a taarikh algorithm object.
 *
 * Plugin Namespace: Plugin\TaarikhAlgorithm
 *
 * @Annotation
 */
class TaarikhAlgorithm extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The name of the class of the actual algorithm.
   *
   * @var string
   */
  public $algorithm_class;

}
