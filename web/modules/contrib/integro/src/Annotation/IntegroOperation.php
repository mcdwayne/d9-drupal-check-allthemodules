<?php

namespace Drupal\integro\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the integro operations plugin annotation object.
 *
 * @Annotation
 */
class IntegroOperation extends Plugin {

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
