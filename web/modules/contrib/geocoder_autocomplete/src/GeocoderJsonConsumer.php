<?php

namespace Drupal\geocoder_autocomplete;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Component\Utility\Html;
use GuzzleHttp\ClientInterface;

/**
 * Defines the GeocoderJsonConsumer service, for return parse GeoJson.
 */
class GeocoderJsonConsumer {

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
   * @param string $text
   *   The text query for search a place.
   *
   * @return array
   *   An array of matching location.
   */
  public function getAddress($text) {
    $language_interface = $this->languageManager->getCurrentLanguage();
    $language = isset($language_interface) ? $language_interface->getId() : 'en';
    $config = \Drupal::config('geocoder_autocomplete.settings');

    $query = [
      'address' => $text,
      'language' => $language,
      'sensor' => 'false',
      'region' => $config->get('region_code_bias'),
    ];
    $uri = 'http://maps.googleapis.com/maps/api/geocode/json';

    $response = $this->httpClient->request('GET', $uri, [
      'query' => $query,
    ]);

    $matches = [];
    if (empty($response->error)) {
      $data = json_decode($response->getBody());
      if ($data->status == 'OK') {
        foreach ($data->results as $result) {
          if (!empty($result->formatted_address)) {
            $formatted_address = $result->formatted_address;
            $matches[] = [
              'value' => Html::escape($formatted_address),
              'label' => Html::escape($formatted_address),
            ];
          }
        }

      }
    }

    return $matches;
  }

}
