<?php

namespace Drupal\entity_pilot;

/**
 * Defines an interface for mock transport implementations.
 */
interface MockTransportInterface extends TransportInterface {

  /**
   * Gets the current send response.
   *
   * @return int
   *   The remote ID that will be returned.
   */
  public function getSendReturn();

  /**
   * Gets the current query return.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface[]
   *   The results would be returned from the array.
   */
  public function getQueryReturn();

  /**
   * Gets the current Exception.
   *
   * @return \Drupal\entity_pilot\Exception\TransportException
   *   The current exception.
   */
  public function getExceptionReturn();

  /**
   * Sets the ID to use for the next send return.
   *
   * @param int $remote_id
   *   The remote ID to return on next send call.
   *
   * @return self
   *   The method on which the instance was called.
   */
  public function setSendReturn($remote_id);

  /**
   * Sets an Exception to occur on the next query or send call.
   *
   * @param string $message
   *   The exception message to return.
   *
   * @return self
   *   The method on which the instance was called.
   */
  public function setExceptionReturn($message);

  /**
   * Sets the array of flights to use for the next query return.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface[] $flights
   *   The array of flight manifests to return.
   *
   * @return self
   *   The method on which the instance was called.
   */
  public function setQueryReturn(array $flights);

  /**
   * Gets the last sent flight.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface
   *   The last sent flight.
   */
  public function getLastSentFlight();

}
