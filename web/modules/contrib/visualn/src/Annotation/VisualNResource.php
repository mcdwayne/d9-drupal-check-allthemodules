<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Resource item annotation object.
 *
 * @see \Drupal\visualn\Manager\ResourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNResource extends Plugin {


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
   * The data output type of the plugin.
   *
   * @var string
   */
  public $output = 'visualn_generic_data_array';

  // @todo: maybe add $context = []

}
