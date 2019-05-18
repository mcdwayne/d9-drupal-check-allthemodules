<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for rental instances.
 */
interface RentalInstanceInterface extends ContentEntityInterface, EntityChangedInterface {
  /**
   * Gets the rental instance state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The order state.
   */
  public function getState();
}
