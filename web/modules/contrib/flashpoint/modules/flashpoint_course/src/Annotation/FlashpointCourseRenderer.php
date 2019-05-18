<?php

namespace Drupal\flashpoint_course\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FlashpointCourseRenderer annotation object.
 *
 * Plugin Namespace: Plugin\flashpoint_course
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FlashpointCourseRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the FlashpointCourseRenderer.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the FlashpointCourseRenderer should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}