<?php

namespace Drupal\vde_drush\Annotation;

use Drupal\Component\Annotation\Plugin;


/**
 * Defines a Format Manipulator type annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FormatManipulator extends Plugin {

  /**
   * Plugin target format.
   *
   * @type string
   */
  public $id;

}
