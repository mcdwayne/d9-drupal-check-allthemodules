<?php

namespace Drupal\entity_pilot;

/**
 * Provides an interface defining a Entity Pilot flight entity.
 */
interface DepartureInterface extends FlightInterface {

  /**
   * Gets the passengers (entities) in the departure.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entities.
   */
  public function getPassengers();

  /**
   * Creates a manifest of the departure.
   *
   * @param array $passengers
   *   Array of normalized passengers.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface
   *   The manifest for the flight.
   */
  public function createManifest(array $passengers);

  /**
   * Sets the passengers on the flight.
   *
   * @param array $passengers
   *   Array of passengers containing entity_type and target_id.
   *
   * @return $this
   */
  public function setPassengers(array $passengers);

}
