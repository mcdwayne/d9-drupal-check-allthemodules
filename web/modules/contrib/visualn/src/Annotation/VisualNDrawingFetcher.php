<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Drawing Fetcher item annotation object.
 *
 * @see \Drupal\visualn\Manager\DrawingFetcherManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDrawingFetcher extends Plugin {


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
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  // @todo: uncomment if needed
  //public $context = [];


  /*
  public function __construct($values) {
    // @todo: here default contexts may be set though seems to be not required
    parent::__construct($values);
  }
  */

}
