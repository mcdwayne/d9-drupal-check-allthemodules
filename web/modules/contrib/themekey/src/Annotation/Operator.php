<?php

/**
 * @file
 * Contains \Drupal\themekey\Annotation\Operator.
 */

namespace Drupal\themekey\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an operator item annotation object.
 *
 * Plugin Namespace: Plugin\themekey\operator
 *
 * @see plugin_api
 *
 * @Annotation
 */
class Operator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the operator.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the operator.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
