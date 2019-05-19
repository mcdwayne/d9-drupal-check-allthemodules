<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Smart reaction item annotation object.
 *
 * @see \Drupal\smart_content\Reaction\SmartReactionManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartReaction extends Plugin {

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
   * The plugin ID.
   *
   * @var boolean
   */
  public $has_configuration_form;
}
