<?php

namespace Drupal\commerce_product_reservation;

use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * SelectedStore service.
 */
class SelectedStoreManager {

  const SELECTED_STORE_STORE = 'commerce_product_reservation:selected_store';
  const SELECTED_STORE_KEY = 'selected_store';

  /**
   * Temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * SelectedStore constructor.
   */
  public function __construct(PrivateTempStoreFactory $private_temp_store) {
    $this->tempStore = $private_temp_store->get(static::SELECTED_STORE_STORE);
  }

  /**
   * Get selected store.
   *
   * @return \Drupal\commerce_product_reservation\ReservationStore
   *   A store we have chosen.
   */
  public function getSelectedStore() {
    return $this->tempStore->get(static::SELECTED_STORE_KEY);
  }

  /**
   * Set store.
   *
   * @param string $store_provider_id
   *   Provider id.
   * @param string $store_id
   *   Store id.
   */
  public function setSelectedStore($store_provider_id, $store_id) {
    $store = ReservationStore::createFromValues([
      'id' => $store_id,
      'provider' => $store_provider_id,
    ]);
    $this->tempStore->set(static::SELECTED_STORE_KEY, $store);
  }

}
