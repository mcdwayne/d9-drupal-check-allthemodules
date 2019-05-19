<?php

namespace Drupal\task\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TaskAction annotation object.
 *
 * Plugin Namespace: Plugin\task
 *
 * @see plugin_api
 *
 * @Annotation
 */
class TaskAction extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the TaskAction.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the TaskAction should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}