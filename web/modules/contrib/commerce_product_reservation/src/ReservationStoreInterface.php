<?php

namespace Drupal\commerce_product_reservation;

/**
 * Interface for reservation_store plugins.
 */
interface ReservationStoreInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Return an array of stores that can be used.
   *
   * @return \Drupal\commerce_product_reservation\ReservationStore[]
   *   Stores.
   */
  public function getStores();

  /**
   * Find info about whether or not we have things in stock.
   *
   * @param \Drupal\commerce_product_reservation\ReservationStore[] $stores
   *   A list of stores.
   * @param array $products
   *   A list of products.
   */
  public function getStockByStoresAndProducts(array $stores, array $products);

}
