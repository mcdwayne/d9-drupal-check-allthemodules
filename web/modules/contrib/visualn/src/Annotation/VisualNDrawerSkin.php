<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Drawer Skin item annotation object.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerSkinManager
 * @see plugin_api
 *
 * @ingroup drawer_skin_plugins
 *
 * @Annotation
 */
class VisualNDrawerSkin extends Plugin {


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
   * The list of compatible base drawers ids.
   *
   * @var array
   */
  public $compatible_drawers = [];

  /**
   * The skin type.
   *
   * @var string
   */
  public $skin_type;

}
