<?php

namespace Drupal\sourcepoint\Api;

use GuzzleHttp\ClientInterface as HttpClientInterface;

/**
 * Class Client.
 *
 * @package Drupal\sourcepoint\Api\Client
 */
class HttpClient implements ClientInterface {

  /**
   * API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Client constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   HTTP client.
   */
  public function __construct(HttpClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiKey($api_key) {
    $this->apiKey = $api_key;
  }

  /**
   * {@inheritdoc}
   */
  public function request($url) {
    $response = $this->httpClient->request('GET', $url, [
      'headers' => [
        'Authorization' => 'Token token=' . $this->apiKey,
      ],
    ]);
    return $response->getBody();
  }

}
