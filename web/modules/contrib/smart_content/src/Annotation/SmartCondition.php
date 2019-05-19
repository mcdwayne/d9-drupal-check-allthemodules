<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Smart condition item annotation object.
 *
 * @see \Drupal\smart_content\Plugin\SmartConditionManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartCondition extends Plugin {

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
   * The field name.
   *
   * @var string
   */
  public $unique = FALSE;

}
