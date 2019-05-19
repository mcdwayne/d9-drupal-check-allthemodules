<?php

namespace Drupal\uc_cart\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a checkout pane annotation object.
 *
 * @Annotation
 */
class CheckoutPane extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the checkout pane.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
