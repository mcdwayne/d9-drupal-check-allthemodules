<?php

namespace Drupal\entity_pilot\Event;

use Drupal\entity_pilot\ArrivalInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines a class for handling a prepare passengers event.
 */
class PreparePassengersEvent extends Event {

  /**
   * Serialized entities keyed by UUID.
   *
   * @var array
   */
  protected $passengers = [];

  /**
   * Arrival from which the passengers were derived.
   *
   * @var \Drupal\entity_pilot\ArrivalInterface
   */
  protected $arrival;

  /**
   * Constructs a new PreparePassengersEvent object.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   Arrival containing the passengers.
   * @param array $passengers
   *   Serialized entities keyed by UUID.
   */
  public function __construct(ArrivalInterface $arrival, array $passengers = []) {
    $this->passengers = $passengers;
    $this->arrival = $arrival;
  }

  /**
   * Gets value of passengers.
   *
   * @return array
   *   Value of passengers
   */
  public function getPassengers() {
    return $this->passengers;
  }

  /**
   * Sets passengers.
   *
   * @param array $passengers
   *   New value for passengers.
   *
   * @return PreparePassengersEvent
   *   Instance called.
   */
  public function setPassengers(array $passengers) {
    $this->passengers = $passengers;
    return $this;
  }

  /**
   * Gets value of arrival.
   *
   * @return \Drupal\entity_pilot\ArrivalInterface
   *   Value of arrival
   */
  public function getArrival() {
    return $this->arrival;
  }

  /**
   * Sets arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   New value for arrival.
   *
   * @return PreparePassengersEvent
   *   Instance called.
   */
  public function setArrival(ArrivalInterface $arrival) {
    $this->arrival = $arrival;
    return $this;
  }

}
