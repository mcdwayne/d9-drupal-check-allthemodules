<?php

namespace Drupal\uc_tax\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a tax rate annotation object.
 *
 * @Annotation
 */
class UbercartTaxRate extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the tax rate.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
