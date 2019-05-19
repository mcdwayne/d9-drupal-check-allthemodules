<?php

namespace Drupal\stacks\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an WidgetType annotation object.
 *
 * Plugin Namespace: Plugin\WidgetType
 *
 * @see \Drupal\stacks\Plugin\WidgetTypeBase
 * @see \Drupal\stacks\Plugin\WidgetTypeInterface
 * @see \Drupal\stacks\Plugin\WidgetTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class WidgetType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
