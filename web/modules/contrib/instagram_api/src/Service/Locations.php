<?php

namespace Drupal\instagram_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Locations.
 *
 * @package Drupal\instagram_api\Service
 */
class Locations {

  /**
   * Client.
   *
   * @var \Drupal\instagram_api\Service\Client
   */
  protected $client;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Media constructor.
   *
   * @param \Drupal\instagram_api\Service\Client $client
   *   Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(Client $client,
                              LoggerChannelFactory $loggerFactory) {
    // Instagram API Client.
    $this->client = $client;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Get information about a location.
   *
   * @param string $locationId
   *   Location ID.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/locations/{location-id}?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/locations/
   */
  public function getLocation($locationId, $cacheable = TRUE) {
    $response = $this->client->request(
      'locations/' . $locationId,
      [],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Get a list of recent media objects from a given location.
   *
   * @param string $locationId
   *   Location ID.
   * @param array $args
   *   Args, see API docs for options.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/locations/{location-id}?access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/locations/
   */
  public function getLocationMediaRecent($locationId, array $args = [], $cacheable = TRUE) {
    $response = $this->client->request(
      'locations/' . $locationId . '/media/recent',
      $args,
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Search for a location by geographic coordinate.
   *
   * @param string $lat
   *   Lat.
   * @param string $lng
   *   Lng.
   * @param string $distance
   *   Dist.
   * @param bool $cacheable
   *   Cacheable.
   *
   * @return array|bool
   *   Response array.
   *   https://api.instagram.com/v1/locations/search?lat=48.858844&lng=2.294351&access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/locations/
   */
  public function searchLocation($lat, $lng, $distance = 500, $cacheable = TRUE) {
    $response = $this->client->request(
      'locations/search',
      [
        'lat' => $lat,
        'lng' => $lng,
        'distance' => $distance,
      ],
      $cacheable
    );

    if ($response) {
      return $response;
    }

    return FALSE;
  }

}
