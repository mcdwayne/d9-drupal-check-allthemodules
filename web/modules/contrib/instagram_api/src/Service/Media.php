<?php

namespace Drupal\instagram_api\Service;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Media.
 *
 * @package Drupal\instagram_api\Service
 */
class Media {

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
   * Search for recent media in a given area.
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
   *   https://api.instagram.com/v1/media/search?lat=48.858844&lng=2.294351&access_token=ACCESS-TOKEN
   *
   * @see https://www.instagram.com/developer/endpoints/media/
   */
  public function mediaSearch($lat, $lng, $distance = 1, $cacheable = TRUE) {
    $response = $this->client->request(
      'media/search',
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
