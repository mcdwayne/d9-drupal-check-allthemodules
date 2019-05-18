<?php

namespace Drupal\flashpoint_course_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FlashpointCourseContentRenderer annotation object.
 *
 * Plugin Namespace: Plugin\flashpoint_course_content_renderer
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FlashpointCourseContentRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the FlashpointCourseContentRenderer.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the FlashpointCourseContentRenderer should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}