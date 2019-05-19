<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Smart condition type item annotation object.
 *
 * @see \Drupal\smart_content\ConditionType\ConditionTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartConditionType extends Plugin {

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
