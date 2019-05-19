<?php

/**
 * @file
 * Contains \Drupal\themekey\Annotation\Property.
 */

namespace Drupal\themekey\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a property item annotation object.
 *
 * Plugin Namespace: Plugin\themekey\property
 *
 * @see plugin_api
 *
 * @Annotation
 */
class PropertyAdmin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
