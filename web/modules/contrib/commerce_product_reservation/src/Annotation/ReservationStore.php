<?php

namespace Drupal\commerce_product_reservation\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines reservation_store annotation object.
 *
 * @Annotation
 */
class ReservationStore extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
