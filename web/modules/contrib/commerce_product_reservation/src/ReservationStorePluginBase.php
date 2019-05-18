<?php

namespace Drupal\commerce_product_reservation;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for reservation_store plugins.
 */
abstract class ReservationStorePluginBase extends PluginBase implements ReservationStoreInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
