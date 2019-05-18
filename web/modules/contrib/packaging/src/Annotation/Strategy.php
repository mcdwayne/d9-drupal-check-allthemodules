<?php

/**
 * @file
 * Contains \Drupal\packaging\Annotation\Strategy.
 */

namespace Drupal\packaging\Annotation;

use Drupal\Component\Annotation\Plugin;


/**
 * Defines a Strategy annotation object.
 *
 * @Annotation
 */
class Strategy extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the strategy.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label;

}
