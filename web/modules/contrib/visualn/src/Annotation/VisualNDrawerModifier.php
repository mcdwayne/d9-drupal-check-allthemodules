<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Drawer Modifier item annotation object.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerModifierManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDrawerModifier extends Plugin {


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
