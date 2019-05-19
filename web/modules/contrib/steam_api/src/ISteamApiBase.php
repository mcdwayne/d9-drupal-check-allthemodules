<?php

namespace Drupal\steam_api;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use GuzzleHttp\Client;

/**
 * Steam API base.
 */
class ISteamApiBase implements ISteamApiBaseInterface {

  /**
   * Guzzle Http Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Steam API Base URL.
   *
   * @var string
   */
  protected $apiBaseUrl;

  /**
   * Steam API Key.
   *
   * @var string
   */
  protected $steamapikey;

  /**
   * ISteamApiBase constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   A guzzle http client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory) {
    $this->httpClient = $http_client;
    $this->apiBaseUrl = Data::STEAM_API_BASE_URL;
    $this->steamapikey = $config_factory->getEditable('steam_api.settings')
      ->get('steam_apikey');
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(string $api_url, array $options) {
    $url = Url::fromUri($api_url, $options)->toString();

    try {
      $request = $this->httpClient->request('GET', $url, ['verify' => FALSE]);
      $response = $request->getBody()->getContents();
      $response = json_decode($response, TRUE);

      return $response;
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

}
