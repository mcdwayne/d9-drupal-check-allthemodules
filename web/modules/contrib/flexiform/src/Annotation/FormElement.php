<?php

namespace Drupal\flexiform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flexiform form entity plugin annotation object.
 *
 * Plugin Namespace: Plugin\FormElement.
 *
 * @see \Drupal\flexiform\FormElementInterface
 * @see \Drupal\flexiform\FormElementBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FormElement extends Plugin {

  /**
   * The form element plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the form element.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The name of the module providing the element.
   *
   * @var string
   */
  public $module;

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context = [];

}
