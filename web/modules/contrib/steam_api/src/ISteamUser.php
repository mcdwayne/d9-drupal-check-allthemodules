<?php

namespace Drupal\steam_api;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;

/**
 * ISteamUser API call.
 */
class ISteamUser extends ISteamApiBase implements ISteamUserInterface {

  /**
   * ISteamUser constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   A guzzle http client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory) {
    parent::__construct($http_client, $config_factory);
    $this->apiBaseUrl = "$this->apiBaseUrl/ISteamUser";
  }

  /**
   * {@inheritdoc}
   */
  public function getFriendList(string $steamcommunity_id) {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetFriendList/v1/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'steamid' => $steamcommunity_id,
      ],
    ];

    $response = $this->getResponse($api_url, $options);

    return $response['friendslist']['friends'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlayerBans(string $steamcommunity_ids) {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetPlayerBans/v1/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'steamids' => $steamcommunity_ids,
      ],
    ];

    $response = $this->getResponse($api_url, $options);

    return $response['response']['players'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlayerSummaries(string $steamcommunity_ids) {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetPlayerSummaries/v0002/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'steamids' => $steamcommunity_ids,
      ],
    ];

    $response = $this->getResponse($api_url, $options);

    return $response['response']['players'] ?? [];
  }

}
