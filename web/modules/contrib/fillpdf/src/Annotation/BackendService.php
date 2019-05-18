<?php

namespace Drupal\fillpdf\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FillPDF BackendService item annotation object.
 *
 * @see \Drupal\fillpdf\Plugin\BackendServiceManager
 * @see plugin_api
 *
 * @Annotation
 */
class BackendService extends Plugin {


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
