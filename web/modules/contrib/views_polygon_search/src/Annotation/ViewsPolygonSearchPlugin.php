<?php

namespace Drupal\views_polygon_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a reusable form plugin annotation object.
 *
 * @Annotation
 */
class ViewsPolygonSearchPlugin extends Plugin {

  /**
   * The backend plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the backend plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
