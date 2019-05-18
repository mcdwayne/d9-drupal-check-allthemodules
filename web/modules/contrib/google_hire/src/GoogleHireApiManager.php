<?php

namespace Drupal\google_hire;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * GoogleHireApiManager.
 */
class GoogleHireApiManager {

  /**
   * Google Hire base URL.
   */
  const GOOGLE_HIRE_FEED_BASE_URL = 'https://hire.withgoogle.com/v2/api/t';

  /**
   * Number of minutes to cache Google Hire data.
   */
  const GOOGLE_HIRE_DATA_CACHE_MINUTES = 2;

  /**
   * Google Hire module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $googleHireConfig;

  /**
   * Guzzle http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * CacheBackendInterface object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs the GoogleHire API Manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, CacheBackendInterface $cache) {
    $this->googleHireConfig = $config_factory->get('google_hire.settings');
    $this->httpClient = $http_client;
    $this->cache = $cache;
  }

  /**
   * Get Google Hire positions.
   *
   * @param bool $reset
   *   If TRUE, cached values will be ignored.
   *
   * @return array
   *   An array of Google Hire positions.
   */
  public function getPositions($reset = FALSE) {
    $cache_key = 'google_hire.google_hire_positions';
    if (!$reset && $cache = $this->cache->get($cache_key)) {
      $positions = $cache->data;
    }
    else {
      try {
        $positions_url = self::GOOGLE_HIRE_FEED_BASE_URL . '/' . $this->googleHireDomain() . '/public/jobs';
        $response = $this->httpClient->get($positions_url, $this->getRequestHeaders());
        $positions = json_decode($response->getBody());

        // Sort positions alphabetically by title.
        usort($positions, function ($a, $b) {
          return strcmp($a->title, $b->title);
        });
        $this->cache->set($cache_key, $positions, $this->cacheExpirationTimestamp());
      }
      catch (Exception $e) {
        $this->getLogger('google_hire')->error('Error accessing Google Hire: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
    return $positions;
  }

  /**
   * Get position links.
   *
   * @return array
   *   An array of positions names linked to their detail pages.
   */
  public function getPositionDetailLinks() {
    $positions = $this->getPositions();
    if (empty($positions)) {
      return [];
    }
    foreach ($positions as $position) {
      $position_links[] = Link::fromTextAndUrl($position->title, Url::fromUri($position->url));
    }
    return $position_links;
  }

  /**
   * Retrieve Google Hire domain for use by the API request.
   *
   * @return string
   *   A string with special characters.
   */
  protected function googleHireDomain() {
    return str_replace('.', '', $this->googleHireConfig->get('google_hire_domain'));
  }

  /**
   * Generates a default cache expiration timestamp for Google Hire data.
   *
   * @return string
   *   A timestamp string.
   */
  protected function cacheExpirationTimestamp() {
    return time() + (self::GOOGLE_HIRE_DATA_CACHE_MINUTES * 60);
  }

  /**
   * Generates standard Google Hire GET request headers.
   *
   * @return array
   *   An array of headers.
   */
  protected function getRequestHeaders() {
    return [
      'headers' => [
        'Accept' => 'application/json',
      ],
    ];
  }

}
