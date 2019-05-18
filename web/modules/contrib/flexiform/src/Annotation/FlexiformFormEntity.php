<?php

namespace Drupal\flexiform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a flexiform form entity plugin annotation object.
 *
 * Plugin Namespace: Plugin\FlexiformFormEntity.
 *
 * @see \Drupal\flexiform\FlexiformFormEntityManager
 * @see \Drupal\flexiform\FlexiformFormEntityInterface
 * @see \Drupal\flexiform\FlexiformFormEntityBase
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FlexiformFormEntity extends Plugin {

  /**
   * The condition plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the condition.
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

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context = [];

}
