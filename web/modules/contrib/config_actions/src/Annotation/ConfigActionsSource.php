<?php

namespace Drupal\config_actions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ConfigActionsSource annotation object.
 *
 * Valid keys:
 *   'id' string - the unique ID of the plugin
 *   'description' string - an optional description of the plugin. Use the
 *     @Translation() function to provide translation of your text.
 *
 * @ingroup config_actions
 *
 * @Annotation
 */
class ConfigActionsSource extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The description of the plugin
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight = 0;

}
