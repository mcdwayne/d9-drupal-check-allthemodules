<?php

namespace Drupal\bibcite\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Processor item annotation object.
 *
 * @see \Drupal\bibcite\Plugin\BibCiteProcessorManager
 * @see plugin_api
 *
 * @Annotation
 */
class BibCiteProcessor extends Plugin {


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
