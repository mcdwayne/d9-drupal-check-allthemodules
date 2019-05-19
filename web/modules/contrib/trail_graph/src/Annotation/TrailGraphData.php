<?php

namespace Drupal\trail_graph\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Trail graph data item annotation object.
 *
 * @see \Drupal\trail_graph\Plugin\TrailGraphDataManager
 * @see plugin_api
 *
 * @Annotation
 */
class TrailGraphData extends Plugin {


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
