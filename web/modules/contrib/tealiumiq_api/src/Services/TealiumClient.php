<?php

namespace Drupal\tealiumiq_api\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client as GuzzleClient;
use Drupal\Core\Url;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Client.
 *
 * @package Drupal\tealiumiq_api\Service
 */
class TealiumClient {

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
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Cache Backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  private $apiBaseUri;

  private $apiKey;
  private $guzzleClient;

  /**
   * Tealium Client constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(ConfigFactory $config,
                              CacheBackendInterface $cacheBackend,
                              TranslationInterface $stringTranslation,
                              LoggerChannelFactoryInterface $loggerFactory) {
    // Get the config.
    $this->config = $config->get('tealiumiq_api.settings');

    // Cache Backend.
    $this->cacheBackend = $cacheBackend;

    // String Translation.
    $this->stringTranslation = $stringTranslation;

    // Settings.
    $this->apiBaseUri = $this->config->get('api_base_uri');
    $this->apiKey = $this->config->get('api_key');

    // Guzzle Client.
    $this->guzzleClient = new GuzzleClient([
      'base_uri' => $this->apiBaseUri,
    ]);

    // Logger Factory.
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Tealium API request.
   *
   * @param string $method
   *   Method to call.
   * @param array $args
   *   Args to request.
   *
   * @return bool|array
   *   Either an array or false.
   */
  public function request($method, array $args) {
    // Build the arg_hash.
    $args = $this->buildArgs($args);
    $response = $this->doRequest($method, $args);

    if ($response) {
      // Return result from source if found.
      return $response;
    }

    // Tough luck, no results mate.
    return FALSE;
  }

  /**
   * Guzzle request for Tealium API.
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
    if ($this->apiKey == "") {
      $msg = $this->t('Tealium API key is not set. It can be set on the <a href=":config_page">configuration page</a>.',
        [':config_page' => Url::fromRoute('tealium_api.settings')]
      );

      // TODO Refactor this.
      drupal_set_message($msg, 'error');
      return FALSE;
    }

    try {
      $response = $this->guzzleClient->request(
        $requestMethod,
        $url,
        [
          'query'   => $parameters,
      // 'Accept'  => 'application/json',.
        ]
      );

      if ($response->getStatusCode() == 200) {
        $contents = $response->getBody()->getContents();
        $json = Json::decode($contents);
        return $json['data'];
      }
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('tealium_api')->error("@message", ['@message' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Build default args.
   *
   * @param array $args
   *   Args to request.
   *
   * @return array
   *   Return the args array.
   */
  private function buildArgs(array $args) {
    // Add in additional parameters then sort them for signing.
    $args['api_key'] = $this->apiKey;
    ksort($args);
    return $args;
  }

}
