<?php

namespace Drupal\breezy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * BreezyApiManager.
 */
class BreezyApiManager {

  /**
   * Breezy API base URL.
   */
  const BREEZY_API_BASE_URL = 'https://breezy.hr/public/api/v3';

  /**
   * Number of days to cache the Breezy access token.
   */
  const BREEZY_TOKEN_CACHE_DAYS = 1;

  /**
   * Number of minutes to cache Breezy data.
   */
  const BREEZY_DATA_CACHE_MINUTES = 2;

  /**
   * Breezy module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $breezyConfig;

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
   * Access token retrieved via the Breezy API.
   *
   * @var string
   */
  protected $breezyAccessToken;

  /**
   * Constructs the Breezy API Manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, CacheBackendInterface $cache) {
    $this->breezyConfig = $config_factory->get('breezy.settings');
    $this->httpClient = $http_client;
    $this->cache = $cache;

    $this->breezyAccessToken = $this->getBreezyAccessToken();
  }

  /**
   * Retrieves Breezy access token.
   *
   * The Breezy access token is valid for 30 days.
   *
   * @param bool $reset
   *   If TRUE, cached values will be ignored.
   *
   * @return string
   *   A Breezy access token string.
   *
   * @see https://developer.breezy.hr/docs/signin
   */
  protected function getBreezyAccessToken($reset = FALSE) {
    $cache_key = 'breezy.breezy_access_token';
    if (!$reset && $cache = $this->cache->get($cache_key)) {
      $access_token = $cache->data;
    }
    else {
      try {
        $response = $this->httpClient->post(self::BREEZY_API_BASE_URL . '/signin', [
          'form_params' => [
            'email' => $this->breezyConfig->get('breezy_email'),
            'password' => $this->breezyConfig->get('breezy_password'),
          ],
        ]);
        $response = json_decode($response->getBody());
        $access_token = $response->access_token;
        $this->cache->set($cache_key, $access_token, $this->breezyTokenCacheExpirationTimestamp());
      }
      catch (Exception $e) {
        $this->getLogger('breezy')->error('Unable to retrieve Breezy access token: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
    return $access_token;
  }

  /**
   * Get Breezy positions.
   *
   * @param bool $reset
   *   If TRUE, cached values will be ignored.
   * @param bool $exclude_pools
   *   If TRUE, exclude Breezy "pool" positions.
   *
   * @return array
   *   An array of Breezy positions.
   */
  public function getPositions($reset = FALSE, $exclude_pools = TRUE) {
    $cache_key = 'breezy.breezy_positions';
    if (!$reset && $cache = $this->cache->get($cache_key)) {
      $positions = $cache->data;
    }
    else {
      try {
        $positions_url = self::BREEZY_API_BASE_URL . '/company/' . $this->breezyConfig->get('breezy_company_id') . '/positions?state=published';
        $response = $this->httpClient->get($positions_url, $this->breezyGetRequestHeaders());
        $positions = json_decode($response->getBody());
        if ($exclude_pools) {
          $positions = array_filter($positions, function ($position) {
            return $position->org_type !== 'pool';
          });
        }
        $this->cache->set($cache_key, $positions, $this->breezyCacheExpirationTimestamp());
      }
      catch (Exception $e) {
        $this->getLogger('breezy')->error('Error accessing Breezy API: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
    return $positions;
  }

  /**
   * Get position data.
   *
   * @param string $position_id
   *   A Breezy position id.
   * @param bool $reset
   *   If TRUE, cached values will be ignored.
   *
   * @return object
   *   The entire Breezy position object.
   */
  public function getPositionData($position_id, $reset = FALSE) {
    $cache_key = 'breezy.breezy_position_' . $position_id;
    if (!$reset && $cache = $this->cache->get($cache_key)) {
      $position = $cache->data;
    }
    else {
      try {
        $positions_url = self::BREEZY_API_BASE_URL . '/company/' . $this->breezyConfig->get('breezy_company_id') . '/position/' . $position_id;
        $response = $this->httpClient->get($positions_url, $this->breezyGetRequestHeaders());
        $position = json_decode($response->getBody());
        $this->cache->set($cache_key, $position, $this->breezyCacheExpirationTimestamp());
      }
      catch (Exception $e) {
        $this->getLogger('breezy')->error('Error accessing Breezy API: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
    return $position;
  }

  /**
   * Retrieves an application URL for a given position.
   *
   * @param string $position_id
   *   A Breezy position id.
   * @param bool $reset
   *   If TRUE, cached values will be ignored.
   *
   * @return Drupal\Core\Url
   *   A Drupal Url object pointing to a Breezy positiona application page.
   */
  public function getPositionApplicationUrl($position_id, $reset = FALSE) {
    $cache_key = 'breezy.breezy_position_application_uri_' . $position_id;
    if (!$reset && $cache = $this->cache->get($cache_key)) {
      $position_application_uri = $cache->data;
    }
    else {
      $company = $this->getCompany();
      $position = $this->getPositionData($position_id);

      $position_application_uri = '//' . $company->friendly_id . '.breezy.hr/p/' . $position->friendly_id . '/apply';
      $this->cache->set($cache_key, $position_application_uri, $this->breezyCacheExpirationTimestamp());
    }

    $position_application_url = Url::fromUri($position_application_uri, [
      'absolute' => TRUE,
      'https' => TRUE,
    ]);
    return $position_application_url;
  }

  /**
   * Retrieves Breezy company data.
   *
   * @param bool $reset
   *   If TRUE, cached values will be ignored.
   *
   * @return object
   *   A Breezy company object.
   */
  protected function getCompany($reset = FALSE) {
    $cache_key = 'breezy.breezy_company';
    if (!$reset && $cache = $this->cache->get($cache_key)) {
      $company = $cache->data;
    }
    else {
      try {
        $company_url = self::BREEZY_API_BASE_URL . '/company/' . $this->breezyConfig->get('breezy_company_id');
        $response = $this->httpClient->get($company_url, $this->breezyGetRequestHeaders());
        $company = json_decode($response->getBody());
        $this->cache->set($cache_key, $company, $this->breezyCacheExpirationTimestamp());
      }
      catch (Exception $e) {
        $this->getLogger('breezy')->error('Error accessing Breezy API: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
    return $company;
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
      $position_links[] = Link::createFromRoute($position->name, 'breezy.position_detail', [
        'position_id' => $position->_id,
      ]);
    }
    return $position_links;
  }

  /**
   * Generates a cache expiration timestamp for the access token.
   *
   * @return string
   *   A timestamp string.
   */
  protected function breezyTokenCacheExpirationTimestamp() {
    return time() + (self::BREEZY_TOKEN_CACHE_DAYS * 24 * 60 * 60);
  }

  /**
   * Generates a default cache expiration timestamp for Breezy data.
   *
   * @return string
   *   A timestamp string.
   */
  protected function breezyCacheExpirationTimestamp() {
    return time() + (self::BREEZY_DATA_CACHE_MINUTES * 60);
  }

  /**
   * Generates standard Breezy GET request headers.
   *
   * @return array
   *   An array of headers.
   */
  protected function breezyGetRequestHeaders() {
    return [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => $this->breezyAccessToken,
      ],
    ];
  }

}
