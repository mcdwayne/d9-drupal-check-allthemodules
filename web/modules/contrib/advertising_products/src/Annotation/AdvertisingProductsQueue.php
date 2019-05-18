<?php

namespace Drupal\advertising_products\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a AdvertisingProductsQueue annotation object.
 *
 * @Annotation
 */
class AdvertisingProductsQueue extends Plugin {

  /**
   * The plugin ID of the queue plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the queue plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the queue plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
