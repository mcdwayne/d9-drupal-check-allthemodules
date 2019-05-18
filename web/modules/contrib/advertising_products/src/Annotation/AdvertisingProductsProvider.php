<?php

namespace Drupal\advertising_products\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a advertising product provider annotation object.
 *
 * @Annotation
 */
class AdvertisingProductsProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the advertising provider provider plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
