<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Smart condition source item annotation object.
 *
 * @see \Drupal\smart_content\ConditionSource\ConditionGroupManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartConditionGroup extends Plugin {

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
