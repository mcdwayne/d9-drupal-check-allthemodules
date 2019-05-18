<?php

namespace Drupal\entity_pilot;

use Drupal\entity_pilot\Utility\FlightStubInterface;

/**
 * Defines an interface for handling flight arrivals and departures.
 */
interface AirTrafficControlInterface {

  /**
   * Sends a single flight.
   *
   * @param \Drupal\entity_pilot\Utility\FlightStubInterface $stub
   *   The departure stub to send.
   */
  public function takeoff(FlightStubInterface $stub);

  /**
   * Receives a single flight.
   *
   * @param \Drupal\entity_pilot\Utility\FlightStubInterface $stub
   *   Arrival stub.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The newly updated/imported entities.
   */
  public function land(FlightStubInterface $stub);

}
