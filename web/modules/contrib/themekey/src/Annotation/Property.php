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
class Property extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the property.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the property.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Indicates if the property is compatible to page caching.
   *
   * @var bool
   */
  public $page_cache_compatible;

}
