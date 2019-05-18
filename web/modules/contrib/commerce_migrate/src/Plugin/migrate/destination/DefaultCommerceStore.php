<?php

namespace Drupal\commerce_migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\commerce_store\Entity\Store;

/**
 * Saves store settings.
 *
 * @MigrateDestination(
 *   id = "default_commerce_store"
 * )
 */
class DefaultCommerceStore extends Config {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $destination = $row->getDestination();
    $store_id = $destination['id'];
    $store = Store::load($store_id);

    $this->config->set('default_store', $store->uuid());
    $this->config->save();
    return [$this->config->getName()];
  }

}
