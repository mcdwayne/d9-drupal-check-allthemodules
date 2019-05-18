<?php

namespace Drupal\integro\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the integro integration plugin annotation object.
 *
 * @Annotation
 */
class IntegroIntegration extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
