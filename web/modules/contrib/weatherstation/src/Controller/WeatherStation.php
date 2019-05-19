<?php

namespace Drupal\weatherstation\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class WeatherStation.
 *
 * @package Drupal\weatherstation\Controller.
 */
class WeatherStation extends ControllerBase {

  /**
   * Get Config service.
   *
   * @var \Drupal\Core\Config\Config
   *   Config service.
   */
  private $config;

  /**
   * Get Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   *   Cache service.
   */
  private $cache;

  /**
   * Get Client http service.
   *
   * @var \GuzzleHttp\ClientInterface
   *   Client http service.
   */
  private $client;

  /**
   * WeatherStation constructor.
   */
  public function __construct(CacheBackendInterface $cache, ClientInterface $client) {
    $this->config = $this->config('weatherstation.settings');
    $this->cache = $cache;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'),
      $container->get('http_client')
    );
  }

  /**
   * Get weather in json.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Weather data in json format.
   */
  public function getWeather() {
    $response = new JsonResponse();
    if (!($data = $this->getData())) {
      $data = array('error' => $this->t('Can not get weather information, check API key'));
    }
    return $response->setData($data);
  }

  /**
   * Select last information about weather from Cache or Openweather API.
   *
   * @return string
   *   Data from database.
   */
  private function getData() {
    $cid = 'weatherstation:weather_info';
    $cache_service = $this->cache;
    $cache = $cache_service->get($cid);
    if (!$cache) {
      $data = $this->refreshData();
      isset($data['error']) ? $time = 1 : $time = $this->config->get('expired') * 60;
      $cache_service->set($cid, $data, REQUEST_TIME + $time);
      return $data;
    }
    return $cache->data;
  }

  /**
   * Refresh data from openweather.
   *
   * @return array|bool|string
   *   True when refresh data was done without error.
   */
  private function refreshData() {
    $api_key = $this->config->get('openweather_api_key');
    $latitude = $this->config->get('lat');
    $longitude = $this->config->get('lon');

    if (empty($latitude) || empty($longitude) || empty($api_key)) {
      return array('error' => $this->t('Empty latitude, longitude or api key'));
    }

    $endpoint_url = "http://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&APPID={$api_key}";
    $client = $this->client;
    $options['http_errors'] = FALSE;
    $response = $client->get($endpoint_url, $options);

    if ($response->getStatusCode() != 200) {
      return array('error' => $response->getReasonPhrase());
    }
    $weather_json = $response->getBody()->getContents();
    return $weather_json;
  }

}
