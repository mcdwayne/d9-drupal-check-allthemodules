<?php

namespace Drupal\config_actions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ConfigActionsPlugin annotation object.
 *
 * Valid keys:
 *   'id' string - the unique ID of the plugin
 *   'description' string - an optional description of the plugin. Use the
 *     @Translation() function to provide translation of your text.
 *   'options' array - an array of options and their default values. This lists
 *     the valid keys that can be specified within the action data accepted by
 *     the plugin. This is a key/value array where the key is the name of the
 *     option and the value is the default value of the option to be used when
 *     the option is not specified in the action data.
 *   'data' array - an optional array of data that is plugin-specific. Consult
 *     the plugin documentation for any values to be used.
 *
 * @ingroup config_actions
 *
 * @Annotation
 */
class ConfigActionsPlugin extends Plugin {

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
   * Options accepted by the plugin.
   *
   * Key/value pairs where the value is the default of the option.
   *
   * @var array
   */
  public $options = [];

  /**
   * List of options that allow string replacement.
   *
   * @var array
   */
  public $replace_in = [];

  /**
   * Plugin specific data.
   *
   * @var array
   */
  public $data = [];

}
