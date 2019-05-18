<?php

namespace Drupal\rel_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a RelatedContent annotation object.
 *
 * @see \Drupal\rel_content\RelatedContentPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class RelatedContent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * A brief, human readable, description of the plugin type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
