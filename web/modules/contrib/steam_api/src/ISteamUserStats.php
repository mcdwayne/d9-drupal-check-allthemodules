<?php

namespace Drupal\steam_api;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;

/**
 * ISteamUserStats API call.
 */
class ISteamUserStats extends ISteamApiBase implements ISteamUserStatsInterface {

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
    $this->apiBaseUrl = "$this->apiBaseUrl/ISteamUserStats";
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalAchievementPercentagesForApp(string $app_id) {
    $api_url = "$this->apiBaseUrl/GetGlobalAchievementPercentagesForApp/v0002/";
    $options = [
      'query' => [
        'appid' => $app_id,
        'format' => 'json',
      ],
    ];

    $response = $this->getResponse($api_url, $options);

    return $response['achievementpercentages']['achievements'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlayerAchievements(string $steamcommunity_id, string $app_id, string $language = '') {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetPlayerAchievements/v0001/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'steamid' => $steamcommunity_id,
        'appid' => $app_id,
      ],
    ];
    if (!empty($language)) {
      $options['query']['l'] = $language;
    }

    $response = $this->getResponse($api_url, $options);

    return $response['playerstats']['achievements'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaForGame(string $app_id, string $language = '') {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetSchemaForGame/v0002/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'appid' => $app_id,
      ],
    ];
    if (!empty($language)) {
      $options['query']['l'] = $language;
    }

    $response = $this->getResponse($api_url, $options);

    return $response['game'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserStatsForGame(string $steamcommunity_id, string $app_id, string $language = '') {
    if (empty($this->steamapikey)) {
      return;
    }

    $api_url = "$this->apiBaseUrl/GetUserStatsForGame/v0002/";
    $options = [
      'query' => [
        'key' => $this->steamapikey,
        'steamid' => $steamcommunity_id,
        'appid' => $app_id,
      ],
    ];
    if (!empty($language)) {
      $options['query']['l'] = $language;
    }

    $response = $this->getResponse($api_url, $options);

    return $response['playerstats']['stats'] ?? [];
  }

}
