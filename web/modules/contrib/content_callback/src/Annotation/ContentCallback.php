<?php

/**
 * @file
 * Contains \Drupal\content_callback\Annotation\ContentCallback.
 */

namespace Drupal\content_callback\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for content callbacks.
 *
 * @Annotation
 */
class ContentCallback extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * A list of entity types the content callback applies to.
   *
   * @var array
   */
  public $entity_types;

  /**
   * Defines if the callbakc has custom options
   *
   * @var boolean
   */
  public $has_options;

}
