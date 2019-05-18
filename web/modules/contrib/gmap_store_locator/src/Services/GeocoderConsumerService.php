<?php

namespace Drupal\store_locator\Services;

use Drupal\Core\Language\LanguageManagerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\Json;

/**
 * Defines the GeocoderConsumerService service, for return parse GeoJson.
 */
class GeocoderConsumerService {

  /**
   * Drupal http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Service constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
      ClientInterface $http_client,
      LanguageManagerInterface $language_manager
  ) {
    $this->httpClient = $http_client;
    $this->languageManager = $language_manager;
  }

  /**
   * Return json list of geolocation matching $text.
   *
   * @param string $address
   *   The address query for search a place.
   *
   * @return array
   *   An array of matching location.
   */
  public function geoLatLong($address) {
    $language_interface = $this->languageManager->getCurrentLanguage();
    $language = isset($language_interface) ? $language_interface->getId() : 'en';

    $query = [
      'address' => $address,
      'language' => $language,
      'sensor' => 'false',
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

}
