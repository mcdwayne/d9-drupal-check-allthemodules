<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for rental reservations.
 */
interface RentalReservationInterface extends ContentEntityInterface, EntityChangedInterface {
  /**
   * Gets the rental reservation state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The order state.
   */
  public function getState();
}
