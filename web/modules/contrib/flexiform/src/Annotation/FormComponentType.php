<?php

namespace Drupal\flexiform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flexiform form entity plugin annotation object.
 *
 * Plugin Namespace: Plugin\FormComponentType.
 *
 * @see \Drupal\flexiform\FormComponentTypePluginManager
 * @see \Drupal\flexiform\FormComponentTypeInterface
 * @see \Drupal\flexiform\FormComponentTypeBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FormComponentType extends Plugin {

  /**
   * The component type plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the form compoenent type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The name of the module providing the type.
   *
   * @var string
   */
  public $module;

}
