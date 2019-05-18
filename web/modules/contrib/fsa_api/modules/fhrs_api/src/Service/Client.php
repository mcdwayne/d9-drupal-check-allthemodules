<?php

namespace Drupal\fhrs_api\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class FSA Client.
 *
 * @package Drupal\fhrs_api\Service
 */
class Client {

  use StringTranslationTrait;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Cache Backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Client constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(ConfigFactory $config,
                              CacheBackendInterface $cacheBackend,
                              TranslationInterface $stringTranslation,
                              LoggerChannelFactory $loggerFactory) {
    // Get the config.
    $this->config = $config->get('fhrs_api.settings');

    // Cache Backend.
    $this->cacheBackend = $cacheBackend;

    // String Translation.
    $this->stringTranslation = $stringTranslation;

    $this->api_base_uri = $this->config->get('api_base_uri');
    $this->api_cache_maximum_age = $this->config->get('api_cache_maximum_age');

    $this->guzzleClient = new GuzzleClient([
      'base_uri' => $this->api_base_uri,
      'headers' => [
        'accept' => 'application/json',
        'x-api-version' => 2,
      ],
    ]);

    $this->loggerFactory = $loggerFactory;

  }

  /**
   * FSA Request.
   *
   * @param string $url
   *   Method to call.
   * @param array $args
   *   Args to request.
   * @param bool $cacheable
   *   Is it cachable.
   *
   * @return bool|array
   *   Either an array or false.
   */
  public function request($url, array $args = [], $cacheable = TRUE) {
    // Build the arg_hash.
    $cid = $this->buildArgHash($args, $url);

    // Check cache.
    if ($cache = $this->cacheBackend->get($cid)) {
      $response = $cache->data;

      // Return result from cache if found.
      return $response;
    }
    // No cache. Do it the hard way.
    else {
      $response = $this->doRequest($url, $args);
      if ($response) {
        // Cache the response if we got one.
        if ($this->api_cache_maximum_age != 0 && $cacheable == TRUE) {
          $this->cacheBackend->set($cid, $response, time() + $this->api_cache_maximum_age);
        }

        // Return result from source if found.
        return $response;
      }
    }

    // Tough luck, no results mate.
    return FALSE;
  }

  /**
   * Build Hash from Args array.
   *
   * @param array $args
   *   Args to request.
   * @param string $url
   *   URL.
   *
   * @return string
   *   Return string.
   */
  private function buildArgHash(array $args, $url) {
    // Build an argument hash we'll use it for the cache id.
    $argHash = $url;

    foreach ($args as $k => $v) {
      $argHash .= $k . $v;
    }

    $argHash = 'fhrs_api:' . md5($argHash);

    return $argHash;
  }

  /**
   * Guzzle request for FSA.
   *
   * @param string $url
   *   Url.
   * @param array $parameters
   *   Parameters.
   * @param string $requestMethod
   *   Request method.
   *
   * @return bool|array
   *   False or array.
   */
  private function doRequest($url, array $parameters = [], $requestMethod = 'GET') {
    try {
      $response = $this->guzzleClient->request(
        $requestMethod,
        $url,
        ['query' => $parameters]
      );

      if ($response->getStatusCode() == 200) {
        $contents = $response->getBody()->getContents();
        $json = Json::decode($contents);
        // kint($json);
        // TODO Add Debugging options.
        return $json;
      }
    }
    catch (GuzzleException $e) {
      // kint($e);
      // TODO Add Debugging options.
      $this->loggerFactory->get('fhrs_api')->error("@message", ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

}
