<?php

namespace Drupal\integro\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the integro client plugin annotation object.
 *
 * @Annotation
 */
class IntegroClient extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
