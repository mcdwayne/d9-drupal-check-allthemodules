<?php

namespace Drupal\integro\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the integro definition plugin annotation object.
 *
 * @Annotation
 */
class IntegroDefinition extends Plugin {

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
