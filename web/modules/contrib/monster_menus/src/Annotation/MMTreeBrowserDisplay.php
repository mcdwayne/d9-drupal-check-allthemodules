<?php

namespace Drupal\monster_menus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MMTreeBrowserDisplay annotation object.
 *
 * MMTreeBrowserDisplay alter data for MMTreeBrowserController to render various
 * entities.
 *
 * @see plugin_api
 *
 * @Annotation
 *
 */
class MMTreeBrowserDisplay extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the display builder.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description;

}
