<?php

namespace Drupal\flexiform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flexiform form enchancer plugin annotation object.
 *
 * Plugin Namespace: Plugin\FormEnhancer.
 *
 * @see \Drupal\flexiform\FormEnhancerPluginManager
 * @see \Drupal\flexiform\FormEnhancerInterface
 * @see \Drupal\flexiform\FormEnhancerBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FormEnhancer extends Plugin {

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
