<?php

namespace Drupal\stock_photo_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a StockPhotoProvider item annotation object.
 *
 * @Annotation
 */
class StockPhotoProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
