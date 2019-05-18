<?php

namespace Drupal\embedly;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides embedly oEmbed service.
 */
class Embedly {

  /**
   * URL for the oEmbed API.
   */
  const EMBEDLY_OEMBED_URL = 'http://api.embed.ly/1/oembed';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The Embedly API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Constructs a new Embedly object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->apiKey = $this->configFactory->get('embedly.settings')->get('api_key');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Requests oEmbed data from Embedly.
   *
   * @param array $urls
   *   An array of URLs to send to Embedly.
   * @return array
   *   An array of data from Embedly.
   */
  public function oEmbed(array $urls) {
    $urls = implode(',', $urls);

    // Request Embedly.
    // TODO: Handle more than 10 URLs.
    try {
      $response = $this->httpClient->get(static::EMBEDLY_OEMBED_URL . '?key=' . $this->apiKey . '&urls=' . $urls);
      return Json::decode($response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('embedly', $e);
    }

    return [];
  }

}
