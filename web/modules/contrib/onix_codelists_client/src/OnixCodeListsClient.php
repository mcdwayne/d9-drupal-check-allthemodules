<?php

namespace Drupal\onix_codelists_client;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * OnixCodeListsClient service.
 */
class OnixCodeListsClient {

  const CACHE_TAG = 'onix_codelists_client:response_data';

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an OnixCodeListsClient object.
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->logger = $logger_factory->get('onix_codelists_client');
  }

  /**
   * Gets the options.
   *
   * @throws \Exception
   */
  public function getCodelistData($code, $include_codes = TRUE, $use_cache = TRUE) {
    $cid = md5(json_encode([$code, $include_codes, $use_cache]));
    $data = $this->cache->get($cid);
    if ($use_cache && $data) {
      return $data->data;
    }
    $url = sprintf('https://onix-codelists.io/api/v1/codelist/%d?include=%s', $code, $include_codes ? 'codes' : '');
    try {
      $response = $this->httpClient->request('GET', $url);
      $data = json_decode($response->getBody());
      if (empty($data)) {
        throw new \Exception('No JSON data found in onix codelists response');
      }
      $this->cache->set($cid, $data, Cache::PERMANENT, [
        self::CACHE_TAG,
      ]);
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Caught exception when trying to get the codelist. Here is the message: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Clear our own cache.
   */
  public function clearCache() {
    Cache::invalidateTags([
      self::CACHE_TAG,
    ]);
  }

}
