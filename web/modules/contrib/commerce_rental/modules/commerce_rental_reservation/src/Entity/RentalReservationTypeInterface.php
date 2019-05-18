<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for rental variation types.
 */
interface RentalReservationTypeInterface extends ConfigEntityInterface {
  /**
   * Gets the rental reservation type's workflow ID.
   *
   * Used by the $reservation->state field.
   *
   * @return string
   *   The rental reservation type workflow ID.
   */
  public function getWorkflowId();

  /**
   * Sets the workflow ID of the rental reservation type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId($workflow_id);
}
