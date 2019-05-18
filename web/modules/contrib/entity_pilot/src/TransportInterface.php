<?php

namespace Drupal\entity_pilot;

use Drupal\entity_pilot\Data\FlightManifestInterface;

/**
 * Interface for transporting entities via entity pilot.
 */
interface TransportInterface {

  /**
   * URI to Entity Pilot REST API.
   */
  const ENTITY_PILOT_URI = 'https://api.entitypilot.com/api/v1/';

  /**
   * Path of flight end-point relative to API URI.
   */
  const FLIGHT_ENDPOINT = 'flights';

  /**
   * Send a flight to EntityPilot.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $manifest
   *   Flight to send.
   * @param string $secret
   *   The encryption secret.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\entity_pilot\Exception\TransportException
   *
   * @return int
   *   Remote flight ID.
   */
  public function sendFlight(FlightManifestInterface $manifest, $secret);

  /**
   * Search for flights for a given account on Entity Pilot.
   *
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   Account to search in.
   * @param string $search_string
   *   (optional) Remote ID or flight info to search for. Defaults to '', which
   *   returns all available records.
   * @param int $limit
   *   (optional) Number of records. Defaults to 50.
   * @param int $offset
   *   (optional) Record offset, defaults to 0.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface[]
   *   Flight manifest with content set on it.
   */
  public function queryFlights(AccountInterface $account, $search_string = '', $limit = 50, $offset = 0);

  /**
   * Gets a single flight.
   *
   * @param int $remote_id
   *   Remote flight ID.
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   Account for the flight.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface
   *   Flight manifest with content set on it.
   */
  public function getFlight($remote_id, AccountInterface $account);

}
