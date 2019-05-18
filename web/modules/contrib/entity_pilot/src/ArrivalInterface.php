<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rest\LinkManager\TypeLinkManagerInterface;

/**
 * Provides an interface defining an Incoming Entity Pilot flight entity.
 */
interface ArrivalInterface extends FlightInterface {

  /**
   * Gets the json-encoded normalized entities.
   *
   * @return string
   *   Contents of the flight.
   */
  public function getContents();

  /**
   * Returns passengers in the arrival or all passengers.
   *
   * @param string $uuid
   *   (optional) Passenger UUID to return, if NULL returns all records.
   *   Defaults to NULL.
   *
   * @return array
   *   If a UUID is passed, individual normalized passenger, else array of
   *   normalized passengers.
   *
   * @throws \InvalidArgumentException
   *   When the passed ID does not exist in the flight.
   */
  public function getPassengers($uuid = NULL);

  /**
   * Sets contents on the flight.
   *
   * @param string $contents
   *   Array of hal+json encoded entities.
   *
   * @return self
   *   The instance the method was called on.
   */
  public function setContents($contents);

  /**
   * Gets the approved passengers.
   *
   * @return array
   *   Array of approved passenger UUIDs.
   */
  public function getApproved();

  /**
   * Creates a linked departure from the landed arrival.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_link_manager
   *   The type link manager service.
   *
   * @return \Drupal\entity_pilot\DepartureInterface
   *   The created linked departure.
   */
  public function createLinkedDeparture(EntityManagerInterface $entity_manager, TypeLinkManagerInterface $type_link_manager);

  /**
   * Checks if the arrival has a linked departure.
   *
   * @return bool
   *   TRUE if arrival has a linked departure
   */
  public function hasLinkedDeparture();

  /**
   * Gets the linked departure.
   *
   * @return \Drupal\entity_pilot\DepartureInterface
   *   Linked departure.
   */
  public function getLinkedDeparture();

  /**
   * Gets the field map from the remote flight.
   *
   * @return array
   *   Array of fields keyed by entity type and field name.
   */
  public function getFieldMap();

  /**
   * Sets the field map.
   *
   * @param string $field_map
   *   JSON encoded Field map contents.
   *
   * @return \Drupal\entity_pilot\ArrivalInterface
   *   Called instance.
   */
  public function setFieldMap($field_map);

}
