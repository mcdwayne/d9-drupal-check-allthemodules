<?php

namespace Drupal\task\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TaskBundle annotation object.
 *
 * Plugin Namespace: Plugin\task
 *
 * @see plugin_api
 *
 * @Annotation
 */
class TaskBundle extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the TaskBundle.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the TaskBundle should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}