<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for dealing with quarantining incoming content.
 */
interface CustomsInterface {

  /**
   * Sends an incoming flight for screening.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $flight
   *   The flight to screen.
   * @param bool $display_errors
   *   Whether or not to display errors.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of unsaved, screened entities.
   */
  public function screen(ArrivalInterface $flight, $display_errors = TRUE);

  /**
   * Previews how an entity might look when imported.
   *
   * You must screen the flight first.
   *
   * @param string $id
   *   ID of entity to preview.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Unsaved, simulated entity.
   */
  public function previewPassenger($id);

  /**
   * Determine if the incoming entity already exists.
   *
   * @param \Drupal\Core\Entity\EntityInterface $passenger
   *   Unsaved incoming entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   An existing entity if one exists, else FALSE
   */
  public function exists(EntityInterface $passenger);

  /**
   * Approves the incoming flight.
   *
   * @param ArrivalInterface $arrival
   *   The arrival to approve.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of saved entities.
   */
  public function approve(ArrivalInterface $arrival);

  /**
   * Clears the cached screened passengers for the given flight.
   *
   * @param \Drupal\entity_pilot\FlightInterface $flight
   *   Flight to clear cache for.
   */
  public function clearCache(FlightInterface $flight);

  /**
   * Approves a single passenger.
   *
   * @param string $passenger_id
   *   UUID of the passenger to approve.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   *   The saved entity or FALSE if the entity could not be saved.
   */
  public function approvePassenger($passenger_id);

}
