<?php

namespace Drupal\simple_a_b\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a test type.
 *
 * Plugin Namespace: Plugin\simple_a_b\SimpleABType.
 *
 * @see \Drupal\simple_a_b\SimpleABTypeManger
 * @see plugin_api
 *
 * @Annotation
 */
class SimpleABType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the test type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;


  /**
   * The entity target type.
   *
   * @var string
   */
  public $entityTargetType;


  /**
   * The entity description.
   *
   * @var string
   */
  public $entityDescription;

}
