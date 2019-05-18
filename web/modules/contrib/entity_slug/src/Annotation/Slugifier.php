<?php

namespace Drupal\entity_slug\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a slugifier annotation object.
 *
 * Plugin Namespace: Plugin\Slugifier
 *
 * @Annotation
 */
class Slugifier extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

  /**
   * The plugin weight.
   *
   * @var integer
   */
  public $weight;
}
