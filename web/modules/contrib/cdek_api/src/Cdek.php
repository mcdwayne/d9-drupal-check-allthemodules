<?php

namespace Drupal\cdek_api;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Crypt;
use CdekSDK\Requests\PvzListRequest;
use CdekSDK\Common\Pvz;
use CdekSDK\CdekClient;

/**
 * Provides the cdek_api service.
 */
class Cdek {

  /**
   * A config object for the 'cdek_api.settings' configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Factory of HTTP clients.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The CDEK API client.
   *
   * @var \CdekSDK\CdekClient
   */
  protected $cdekClient;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache object associated with the 'cdek_api' bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Array of pickup points keyed by cache key.
   *
   * @var array
   */
  protected static $points = [];

  /**
   * Array of countries keyed by cache key.
   *
   * @var array
   */
  protected static $countries = [];

  /**
   * Array of regions keyed by cache key.
   *
   * @var array
   */
  protected static $regions = [];

  /**
   * Array of cities keyed by cache key.
   *
   * @var array
   */
  protected static $cities = [];

  /**
   * Cdek constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   Factory of HTTP clients.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache object associated with the 'cdek_api' bin.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientFactory $http_client_factory, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache) {
    $this->config = $config_factory->get('cdek_api.settings');
    $this->httpClientFactory = $http_client_factory;
    $this->moduleHandler = $module_handler;
    $this->cache = $cache;
  }

  /**
   * Gets the CDEK API client.
   *
   * @return \CdekSDK\CdekClient
   *   The CDEK API client.
   */
  public function getCdekClient() {
    if (!isset($this->cdekClient)) {
      $http_client = $this->httpClientFactory->fromOptions([
        'base_uri' => CdekClient::STANDARD_BASE_URL,
        'timeout' => (float) $this->config->get('request_timeout'),
      ]);

      $account = (string) $this->config->get('account');
      $password = (string) $this->config->get('password');
      $this->cdekClient = new CdekClient($account, $password, $http_client);
    }
    return $this->cdekClient;
  }

  /**
   * Gets the list of pickup points.
   *
   * @param \CdekSDK\Requests\PvzListRequest|null $request
   *   An instance of the request object.
   *
   * @return \CdekSDK\Common\Pvz[]|null
   *   Array of pickup points keyed by code. NULL on failure.
   */
  public function getPickupPoints(PvzListRequest $request = NULL) {
    $request = $request ?? new PvzListRequest();
    $params = $request->getParams();
    $key = $this->getCacheKey($params);

    if (!isset(static::$points[$key])) {
      $cid = 'pickup_points:' . $key;
      $points = $this->getCacheData($cid);

      if (!isset($points)) {
        try {
          $response = $this->getCdekClient()->sendPvzListRequest($request);
        }
        catch (\Exception $e) {
          return NULL;
        }

        $points = $response->getItems();
        // Use the point code as the key.
        foreach ($points as $index => $point) {
          $points[$point->Code] = $point;
          unset($points[$index]);
        }
        // Sort points alphabetically.
        uasort($points, [$this, 'comparePointsByName']);
        $this->setCacheData($cid, $points);
      }

      // Trigger hook_cdek_api_pickup_points_alter().
      // Allow modules to override the list of pickup points.
      $this->moduleHandler->alter('cdek_api_pickup_points', $points, $params);
      static::$points[$key] = $points;
    }
    return static::$points[$key];
  }

  /**
   * Gets the pickup point.
   *
   * @param string $code
   *   The code of the point to load.
   *
   * @return \CdekSDK\Common\Pvz|null
   *   The point object or NULL if there is no point with the given code.
   *
   * @see \Drupal\cdek_api\Cdek::getPickupPoints()
   */
  public function getPickupPoint($code) {
    $points = $this->getPickupPoints();
    return $points[$code] ?? NULL;
  }

  /**
   * Gets the list of countries.
   *
   * @param \CdekSDK\Requests\PvzListRequest|null $request
   *   An instance of the request object.
   *
   * @return array|null
   *   Array of country names keyed by code. NULL on failure.
   *
   * @see \Drupal\cdek_api\Cdek::getPickupPoints()
   */
  public function getCountries(PvzListRequest $request = NULL) {
    $request = $request ?? new PvzListRequest();
    $key = $this->getCacheKey($request->getParams());

    if (!isset(static::$countries[$key])) {
      $points = $this->getPickupPoints($request);
      if ($points === NULL) {
        return NULL;
      }

      // Extract countries from pickup points.
      $countries = [];
      foreach ($points as $point) {
        if (!isset($countries[$point->CountryCode])) {
          $countries[$point->CountryCode] = $point->CountryName;
        }
      }
      // Sort countries alphabetically.
      uasort($countries, ['Drupal\Component\Utility\Unicode', 'strcasecmp']);
      static::$countries[$key] = $countries;
    }
    return static::$countries[$key];
  }

  /**
   * Gets the list of regions.
   *
   * @param \CdekSDK\Requests\PvzListRequest|null $request
   *   An instance of the request object.
   *
   * @return array|null
   *   Array of region names keyed by code. NULL on failure.
   *
   * @see \Drupal\cdek_api\Cdek::getPickupPoints()
   */
  public function getRegions(PvzListRequest $request = NULL) {
    $request = $request ?? new PvzListRequest();
    $key = $this->getCacheKey($request->getParams());

    if (!isset(static::$regions[$key])) {
      $points = $this->getPickupPoints($request);
      if ($points === NULL) {
        return NULL;
      }

      // Extract regions from pickup points.
      $regions = [];
      foreach ($points as $point) {
        if (!isset($regions[$point->RegionCode])) {
          $regions[$point->RegionCode] = $point->RegionName;
        }
      }
      // Sort regions alphabetically.
      uasort($regions, ['Drupal\Component\Utility\Unicode', 'strcasecmp']);
      static::$regions[$key] = $regions;
    }
    return static::$regions[$key];
  }

  /**
   * Gets the list of cities.
   *
   * @param \CdekSDK\Requests\PvzListRequest|null $request
   *   An instance of the request object.
   *
   * @return array|null
   *   Array of city names keyed by code. NULL on failure.
   *
   * @see \Drupal\cdek_api\Cdek::getPickupPoints()
   */
  public function getCities(PvzListRequest $request = NULL) {
    $request = $request ?? new PvzListRequest();
    $key = $this->getCacheKey($request->getParams());

    if (!isset(static::$cities[$key])) {
      $points = $this->getPickupPoints($request);
      if ($points === NULL) {
        return NULL;
      }

      // Extract cities from pickup points.
      $cities = [];
      foreach ($points as $point) {
        if (!isset($cities[$point->CityCode])) {
          $cities[$point->CityCode] = $point->City;
        }
      }
      // Sort cities alphabetically.
      uasort($cities, ['Drupal\Component\Utility\Unicode', 'strcasecmp']);
      static::$cities[$key] = $cities;
    }
    return static::$cities[$key];
  }

  /**
   * Determines whether a persistent cache is used.
   *
   * @return bool
   *   TRUE if a persistent cache is used and FALSE otherwise.
   */
  public function usesPersistentCache() {
    return $this->config->get('cache_lifetime') !== NULL;
  }

  /**
   * Gets the cache key for a value.
   *
   * @param mixed $value
   *   The value to get the cache key.
   *
   * @return string
   *   The cache key.
   */
  protected function getCacheKey($value) {
    return Crypt::hashBase64(serialize($value));
  }

  /**
   * Returns data from the persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to retrieve.
   *
   * @return mixed|null
   *   The cache data. NULL if no matching data found.
   */
  protected function getCacheData($cid) {
    if ($this->usesPersistentCache()) {
      $item = $this->cache->get($cid);
      return $item->data ?? NULL;
    }
    return NULL;
  }

  /**
   * Stores data in the persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   */
  protected function setCacheData($cid, $data) {
    if ($this->usesPersistentCache()) {
      $expire = $this->config->get('cache_lifetime');
      if ($expire !== CacheBackendInterface::CACHE_PERMANENT) {
        $expire = $expire * 60 + time();
      }
      $this->cache->set($cid, $data, $expire);
    }
  }

  /**
   * Compares two pickup points by their names.
   *
   * @param \CdekSDK\Common\Pvz $point1
   *   The first pickup point.
   * @param \CdekSDK\Common\Pvz $point2
   *   The second pickup point.
   *
   * @return int
   *   Returns < 0 if $point1 is less than $point2; > 0 if $point1 is greater
   *   than $point2, and 0 if they are equal.
   */
  protected function comparePointsByName(Pvz $point1, Pvz $point2) {
    return Unicode::strcasecmp($point1->Name, $point2->Name);
  }

}
