<?php

namespace Drupal\facebook_flush_cache;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\MessageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class FacebookService.
 *
 * @package Drupal\facebook_flush_cache
 */
class FacebookFlushCacheService {

  /**
   * The base url.
   *
   * @var string
   */
  public $facebookUrl = 'https://graph.facebook.com';

  /**
   * Provides HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Provides logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new object.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Make request to clear cache.
   */
  public function clearCache($uri) {

    try {

      $url = $this->buildUrl($uri);

      $request = $this->httpClient->post($url);

      $this->log($request);

      return TRUE;

    }
    catch (RequestException $error) {
      $this->logError($error);

      return FALSE;
    }

  }

  /**
   * Build the url to flush.
   */
  public function buildUrl($uri) {

    $query = ['id' => $uri, 'scrape' => 'true'];

    return sprintf('%s/?%s', $this->facebookUrl, http_build_query($query));
  }

  /**
   * Log successful.
   */
  public function log(MessageInterface $request) {

    $data = $request->getBody()->getContents();

    $data = Json::decode($data);

    \Drupal::logger('facebook_flush_cache')
      ->info("Cache cleared for @url", ['@url' => $data['id']]);
  }

  /**
   * Log Error.
   */
  public function logError(RequestException $error) {
    \Drupal::logger('facebook_flush_cache')
      ->error($error->getMessage());
  }

}
