<?php

namespace Drupal\steam_api;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;

/**
 * ISteamNews API call.
 */
class ISteamNews extends ISteamApiBase implements ISteamNewsInterface {

  /**
   * ISteamNews constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   A guzzle http client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory) {
    parent::__construct($http_client, $config_factory);
    $this->apiBaseUrl = "$this->apiBaseUrl/ISteamNews";
  }

  /**
   * {@inheritdoc}
   */
  public function getNewsForApp(string $appid, int $count, string $feed_names = '') {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetNewsForApp/v0002/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'appid' => $appid,
        'count' => $count,
        'maxlength' => 300,
        'format' => 'json',
      ],
    ];
    if (!empty($feed_names)) {
      $options['feeds'] = $feed_names;
    }

    $response = $this->getResponse($api_url, $options);

    return $response['appnews']['newsitems'] ?? [];
  }

}
