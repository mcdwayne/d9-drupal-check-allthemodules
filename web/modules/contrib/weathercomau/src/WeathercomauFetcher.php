<?php

/**
 * @file
 * Contains \Drupal\weathercomau\WeathercomauFetcher.
 */

namespace Drupal\weathercomau;

use Drupal\Component\Utility\Unicode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class WeathercomauFetcher implements WeathercomauFetcherInterface {

  /**
   * Weather.com.au RSS URL.
   */
  const WCA_DEFAULT_URL = 'http://rss.weather.com.au';

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a WeathercomauFetcher object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getFetchBaseUrl() {
    return self::WCA_DEFAULT_URL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFetchUrl($city, $state) {
    return $this->getFetchBaseUrl() . '/' . Unicode::strtolower($state . '/' . $city);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($city, $state) {
    $data = '';

    try {
      $url = $this->buildFetchUrl($city, $state);
      $options = array('headers' => array('Accept' => 'text/xml'));
      $data = (string) $this->httpClient->get($url, $options)->getBody();
    }
    catch (RequestException $exception) {
      watchdog_exception('weathercomau', $exception);
    }

    return $data;
  }

}
