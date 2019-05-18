<?php

namespace Drupal\plugable;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation for common plugins.
 *
 * @Annotation
 */
abstract class PlugableAnnotation extends Plugin {

  /**
   * The plugin ID.
   * @var string
   */
  public $id;

  /**
   * The translated human-readable plugin name.
   * @var string
   */
  public $label;

  /**
   * The translated human-readable plugin description (optional).
   * @var string
   */
  public $description;

}
