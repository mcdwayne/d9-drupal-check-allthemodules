<?php

namespace Drupal\prepared_data\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation definition class for data provider plugins.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class PreparedDataProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Provider plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The priority of this provider (smaller means earlier matching).
   *
   * @var int
   */
  public $priority;

}
