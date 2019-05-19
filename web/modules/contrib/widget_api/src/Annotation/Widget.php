<?php

namespace Drupal\widget_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a widget annotation object.
 *
 * Widgets are small configurations that allow you to define reusable config
 * componentes for the front end.
 *
 * Plugin namespace: Plugin\Widget
 *
 * @Annotation
 */
class Widget extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @war \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An optional description for advanced widgets.
   *
   * Sometimes widgets are so complex that the name is insufficient to describe
   * a widget such that a visually impaired administrator could widget a page
   * for a non-visually impaired audience. If specified, it will provide a
   * description that is used for accessibility purposes.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The human-readable category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The template file to render this widget (relative to the 'path' given).
   *
   * If specified, then the widget_plugin module will register the template with
   * hook_theme() and the module or theme registering this widget does not need
   * to do it. This is mutually exclusive with 'theme' - you can't specify both.
   *
   * @var string optional
   *
   * @see hook_theme()
   */
  public $template;

  /**
   * The config fields for the widget.
   */
  public $fields;

}
