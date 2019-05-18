<?php

namespace Drupal\elastic_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EntityTypeDefinitions item annotation object.
 *
 * @see \Drupal\elastic_search\Plugin\EntityTypeDefinitions
 * @see plugin_api
 *
 * @Annotation
 * @codeCoverageIgnore
 */
class EntityTypeDefinitions extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
