<?php

namespace Drupal\commerce_product_reservation_simple\Plugin\ReservationStore;

use Drupal\commerce_product_reservation\ReservationStore;
use Drupal\commerce_product_reservation\ReservationStorePluginBase;
use Drupal\commerce_product_reservation\StockResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the reservation_store.
 *
 * @ReservationStore(
 *   id = "simple_store",
 *   label = @Translation("Simple store"),
 *   description = @Translation("A simple implementation of this functionality.")
 * )
 */
class SimpleStore extends ReservationStorePluginBase {

  const STORE_ID = 'simple_store';

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getStockByStoresAndProducts(array $stores, array $products) {
    $return = [];
    foreach ($products as $product) {
      $return[] = StockResult::createFromValues([
        'storeId' => self::STORE_ID,
        'sku' => $product,
        // Always return 1 in stock.
        'stock' => 1,
      ]);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    $store = ReservationStore::createFromValues([
      // @todo: Replace with config values.
      'id' => 'simple_store',
      'name' => $this->t('My store'),
    ]);
    return [
      $store,
    ];
  }

}
