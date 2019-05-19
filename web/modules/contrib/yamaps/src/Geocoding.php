<?php

namespace Drupal\yamaps;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use GuzzleHttp\Client;

/**
 * Class Geocoding.
 */
class Geocoding {

  public const YAMAPS_GEOCODER_URL = '//geocode-maps.yandex.ru/1.x/';

  /**
   * The psr7 Http.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * Get geo data for string.
   *
   * @param string $geolocation_string
   *   Name of geographical object.
   *
   * @return array|null
   *   Geocoding array.
   */
  public function geocode($geolocation_string): ?array {
    if (!$geolocation_string) {
      return NULL;
    }

    // Preparing geocoding string.
    $query = [
      'format' => 'json',
      'geocode' => $geolocation_string,
      'results' => 1,
      'lang' => 'ru',
    ];

    $geolocation_url = Url::fromUri(static::YAMAPS_GEOCODER_URL, ['query' => $query, 'absolute' => TRUE]);
    $geolocation_request = $this->httpClient->get($geolocation_url->toString());
    $geolocation_data = Json::decode($geolocation_request->getBody()->getContents());
    if ($geolocation_data && $geolocation_data['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0) {
      $map_center = $geolocation_data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
      $bounded_by = [
        \array_map('floatval', \array_reverse(\explode(' ', $geolocation_data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['boundedBy']['Envelope']['lowerCorner']))),
        \array_map('floatval', \array_reverse(\explode(' ', $geolocation_data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['boundedBy']['Envelope']['upperCorner']))),
      ];
      return [
        'map_center' => \array_map('floatval', \array_reverse(\explode(' ', $map_center))),
        'bounds' => $bounded_by,
      ];
    }

    return NULL;
  }

  /**
   * Prepares values for js.
   *
   * @param array $params
   *   Params of map.
   *
   * @return array
   *   Prepared values.
   */
  public function decodeParams(array $params): array {
    return [
      'coords' => isset($params['coords']) ? Json::decode($params['coords']) : [],
      'type' => !empty($params['coordstype']) ? $params['coordstype'] : 'yandex#map',
      'placemarks' => Json::decode($params['placemarks']),
      'lines' => Json::decode($params['lines']),
      'polygons' => Json::decode($params['polygons']),
      'routes' => Json::decode($params['routes']),
    ];
  }

}
