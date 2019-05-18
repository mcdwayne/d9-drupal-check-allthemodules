<?php

namespace Drupal\bcubed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Condition plugin item annotation object.
 *
 * @see \Drupal\bcubed\Plugin\ConditionManager
 * @see plugin_api
 *
 * @Annotation
 */
class Condition extends Plugin {


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

  /**
   * Additional administrative information about the condition's behavior.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * The default settings for the condition.
   *
   * @var array (optional)
   */
  public $settings = [];

  /**
   * Whether condition can be included more than once
   *
   * @var bool (optional)
   */
  public $instances = FALSE;

  /**
   * The BCubed plugin dependencies of this plugin.
   *
   * Example dependencies array, in annotation form:
   * bcubed_dependencies = {
   *  {
   *    "plugin_type" = "event",
   *    "plugin_id" = "some_event",
   *    "same_set" = true,
   *    "dependency_type" = "requires",
   *  }
   *}
   *
   * @var array (optional)
   */
  public $bcubed_dependencies = [];

  /**
   * Any generated strings registered by the plugin.
   *
   * @var array (optional)
   */
  public $generated_strings = [];

  /**
   * Key to use for generated strings. Defaults to plugin id.
   *
   * @var string (optional)
   */
  public $generated_strings_dictionary = '';

}
