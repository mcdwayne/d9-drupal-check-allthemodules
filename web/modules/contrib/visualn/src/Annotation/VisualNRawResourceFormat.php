<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Raw Resource Format item annotation object.
 *
 * @see \Drupal\visualn\Manager\RawResourceFormatManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNRawResourceFormat extends Plugin {


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
  public $output = '';

  /**
   * The resource format group e.g. 'default' or 'visualn_file_widget'.
   *
   * @var array
   */
  public $groups = [];

}
