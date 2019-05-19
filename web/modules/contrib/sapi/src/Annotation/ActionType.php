<?php

namespace Drupal\sapi\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Statistics action type item annotation object.
 *
 * @see \Drupal\sapi\ActionTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActionType extends Plugin {

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
