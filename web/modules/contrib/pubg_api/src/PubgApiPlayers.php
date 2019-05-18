<?php

namespace Drupal\pubg_api;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Client;

/**
 * Pubg Api Matches methods.
 */
class PubgApiPlayers extends PubgApiBase implements PubgApiPlayersInterface {

  /**
   * PubgApiPlayers constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   A guzzle http client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory, MessengerInterface $messenger) {
    parent::__construct($http_client, $config_factory, $messenger);
    $this->apiEndpointBase = "players";
  }

  /**
   * {@inheritdoc}
   */
  public function getPlayers(string $shard, string $player_ids = '', string $player_names = '') {
    $endpoint_options = [];

    if (!empty($player_ids)) {
      $endpoint_options['query']['filter[playerIds]'] = $player_ids;
    }

    if (!empty($player_names)) {
      $endpoint_options['query']['filter[playerNames]'] = $player_names;
    }

    $response = $this->getResponse($shard, $this->apiEndpointBase, $endpoint_options);

    return $response ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSinglePlayer(string $shard, string $player_id) {
    $api_endpoint = "{$this->apiEndpointBase}/{$player_id}";
    $endpoint_options = [];

    $response = $this->getResponse($shard, $api_endpoint, $endpoint_options);

    return $response ?? [];
  }

}
