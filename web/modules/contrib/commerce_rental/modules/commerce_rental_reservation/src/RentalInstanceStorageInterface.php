<?php

namespace Drupal\commerce_rental_reservation;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for rental variation storage.
 */
interface RentalInstanceStorageInterface extends ContentEntityStorageInterface {

  /**
   * @param array $states
   * @param string $variation_id
   *
   * @return \Drupal\commerce_rental_reservation\Entity\RentalInstance[]|array
   */
  public function loadMultipleByState($states = [], $variation_id);

}
