<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Annotation\MpacSelection.
 */

namespace Drupal\mpac\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MpacSelection annotation object.
 *
 * @Annotation
 */
class MpacSelection extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the selection plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The selection plugin group.
   *
   * This property is used to allow selection plugins to target a specific type
   * while also inheriting the code of an existing selection plugin.
   * For example, if we want to override the NodeSelection from the 'default'
   * selection type, we can define the annotation of a new plugin as follows:
   * @code
   * id = "node_advanced",
   * types = {"node"},
   * group = "default",
   * weight = 11
   * @endcode
   *
   * @var string
   */
  public $group;

  /**
   * An array of selection types that can be referenced by this plugin. Defaults
   * to all available types.
   *
   * @var array (optional)
   */
  public $types = array();

  /**
   * The weight of the plugin in it's group.
   *
   * @var int
   */
  public $weight;

}
