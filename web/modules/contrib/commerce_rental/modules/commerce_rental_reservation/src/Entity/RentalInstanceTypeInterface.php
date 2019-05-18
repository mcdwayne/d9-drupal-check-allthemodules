<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for rental variation types.
 */
interface RentalInstanceTypeInterface extends ConfigEntityInterface {
  /**
   * Gets the rental instance type's workflow ID.
   *
   * Used by the $instance->state field.
   *
   * @return string
   *   The rental instance type workflow ID.
   */
  public function getWorkflowId();

  /**
   * Sets the workflow ID of the rental instance type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId($workflow_id);

  /**
   * Gets the rental instance type's rental instance selector plugin ID.
   *
   * @return string
   *   The rental instance type selector plugin ID.
   */
  public function getSelectorId();

  /**
   * Sets the rental instance selector plugin ID of the rental instance type.
   *
   * @param string $selector_id
   *   The rental instance selector plugin ID.
   *
   * @return $this
   */
  public function setSelectorId($selector_id);
}
