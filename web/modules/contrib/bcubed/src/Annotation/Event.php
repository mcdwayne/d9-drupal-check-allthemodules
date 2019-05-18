<?php

namespace Drupal\bcubed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Event plugin item annotation object.
 *
 * @see \Drupal\bcubed\Plugin\EventManager
 * @see plugin_api
 *
 * @Annotation
 */
class Event extends Plugin {


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
   * Additional administrative information about the plugin's behavior.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * The default settings for the event.
   *
   * @var array (optional)
   */
  public $settings = [];

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
   *  },
   *  {
   *    "plugin_type" = "condition",
   *    "plugin_id" = "some_condition",
   *    "same_set" = false,
   *    "dependency_type" = "generated_by",
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
