<?php

namespace Drupal\elastic_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Field mapper plugin item annotation object.
 *
 * @see \Drupal\elastic_search\Plugin\FieldMapperManager
 * @see plugin_api
 *
 * @Annotation
 * @codeCoverageIgnore
 */
class FieldMapper extends Plugin {

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
