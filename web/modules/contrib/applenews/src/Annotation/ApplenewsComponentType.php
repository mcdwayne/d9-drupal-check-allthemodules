<?php

namespace Drupal\applenews\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ApplenewsComponentType plugin annotation object.
 *
 * @see \Drupal\applenews\Plugin\ApplenewsComponentTypeManager
 *
 * @see https://github.com/chapter-three/AppleNewsAPI
 *
 * @see plugin_api
 *
 * @Annotation
 */
class ApplenewsComponentType extends Plugin {

  /**
   * The fully qualified class name of the Component class.
   *
   * @var string
   */
  public $component_class;

  /**
   * The type of component based on the content, such at text or url.
   *
   * @var string
   */
  public $component_type;

  /**
   * The data source plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the data source plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the data source.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
