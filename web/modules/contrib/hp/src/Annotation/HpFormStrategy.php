<?php

namespace Drupal\hp\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the hp plugin annotation object.
 *
 * Plugin namespace: Plugin\hp.
 *
 * @Annotation
 */
class HpFormStrategy extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The form strategy plugin label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;
}
