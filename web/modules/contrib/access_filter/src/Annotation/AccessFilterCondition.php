<?php

namespace Drupal\access_filter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for access_filter condition plugins.
 *
 * Plugin Namespace: Plugin\AccessFilter\Condition.
 *
 * @see \Drupal\access_filter\Plugin\AccessFilterPluginManager
 * @see \Drupal\access_filter\Plugin\ConditionInterface
 * @see plugin_api
 *
 * @Annotation
 */
class AccessFilterCondition extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The example snippets for the plugin.
   *
   * @var array
   */
  public $examples;

}
