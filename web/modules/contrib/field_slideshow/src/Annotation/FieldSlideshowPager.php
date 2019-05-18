<?php

namespace Drupal\field_slideshow\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines field_slideshow_pager annotation object.
 *
 * @Annotation
 */
class FieldSlideshowPager extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
