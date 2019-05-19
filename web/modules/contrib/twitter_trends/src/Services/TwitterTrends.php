<?php

namespace Drupal\twitter_trends\Services;

use GuzzleHttp\ClientInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class TwitterTrends.
 *
 * @package Drupal\twitter_trends
 */
class TwitterTrends {
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  /**
   * Drupal http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;
  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructs State Service Object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State Service Object.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http Client Service Object.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   Country Manager Service Object.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger Service Object.
   */
  public function __construct(StateInterface $state, ClientInterface $http_client, CountryManagerInterface $country_manager, LoggerChannelFactory $loggerFactory) {
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->countryManager = $country_manager;
    $this->loggerFactory = $loggerFactory->get('twitter_trends');
  }

  /**
   * Contains process for fetching the latest trends.
   *
   * @param int $total
   *   Total Number of Trends.
   *
   * @return array
   *   An associative array with trends data.
   */
  public function fetchData($total = NULL) {

    if (isset($_COOKIE['latitude']) && isset($_COOKIE['longitude'])) {
      $lat = $_COOKIE['latitude'];
      $lng = $_COOKIE['longitude'];
    }
    else {
      $latlng = $this->geoLatLong();
      $lat = $latlng['latitude'];
      $lng = $latlng['longitude'];
    }
    try {
      $cusumer_key = $this->state->get('twt_consumer_key');
      $consumer_secret = $this->state->get('twt_consumer_secret');
      $access_token = $this->state->get('twt_access_token');
      $access_token_secret = $this->state->get('twt_token_secret');
      $connection = new TwitterOAuth($cusumer_key, $consumer_secret, $access_token, $access_token_secret);
      $closest = $connection->get("trends/closest", ['lat' => $lat, 'long' => $lng]);
      if (!empty($closest) && is_array($closest)) {
        $data = $connection->get("trends/place", ['id' => $closest[0]->woeid]);
        $data = array_slice($data[0]->trends, 0, $total);
      }
      return $data;
    }
    catch (\Exception $e) {
      $this->loggerFactory->error('Error getting from Twitter Response.' . print_r($e->getMessage(), TRUE));
    }

  }

  /**
   * Return json list of geolocation matching $text.
   *
   * @return array
   *   An array of matching location.
   */
  public function geoLatLong() {
    $country_code = \Drupal::config('system.date')->get('country.default');
    try {
      $query = [
        'sensor' => 'false',
        'components' => 'country:' . $country_code,
      ];
      $uri = 'http://maps.googleapis.com/maps/api/geocode/json';

      $response = $this->httpClient->request('GET', $uri, [
        'query' => $query,
      ]);

      if (empty($response->error)) {
        $data = Json::decode($response->getBody());

        if (strtoupper($data['status']) == 'OK') {
          $lat = $data['results'][0]['geometry']['location']['lat'];
          $lng = $data['results'][0]['geometry']['location']['lng'];
          $geocodes = ['latitude' => $lat, 'longitude' => $lng];
        }
      }
      return $geocodes;
    }
    catch (\Exception $e) {
      $this->loggerFactory->error('Error getting on Google Response.' . print_r($e->getMessage(), TRUE));
    }

  }

}
