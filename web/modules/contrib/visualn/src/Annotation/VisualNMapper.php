<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Mapper item annotation object.
 *
 * @see \Drupal\visualn\Manager\MapperManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNMapper extends Plugin {


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

  /**
   * The data input type of the plugin.
   *
   * @var string
   */
  public $input = '';

  /**
   * The data output type of the plugin.
   *
   * @var string
   */
  public $output = '';

}
