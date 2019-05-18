<?php

namespace Drupal\pubg_api;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Client;

/**
 * Pubg Api Matches methods.
 */
class PubgApiMatches extends PubgApiBase implements PubgApiMatchesInterface {

  /**
   * API endpoint base.
   *
   * @var string
   */
  protected $apiEndpointBase;

  /**
   * PubgApiMatches constructor.
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
    $this->apiEndpointBase = "matches";
  }

  /**
   * {@inheritdoc}
   */
  public function getMatches(string $shard, string $player_ids = '', string $game_mode = '', string $filter_start = '', string $filter_end = '', string $sort = 'createdAt', int $limit = 5, int $offset = 0) {
    $endpoint_options = [
      'query' => [
        'sort' => $sort,
        'page[limit]' => $limit,
        'page[offset]' => $offset,
      ],
    ];

    if (!empty($player_ids)) {
      $endpoint_options['query']['filter[playerIds]'] = $player_ids;
    }

    if (!empty($game_mode)) {
      $endpoint_options['query']['filter[gameMode]'] = $game_mode;
    }

    if (!empty($filter_start)) {
      $endpoint_options['query']['filter[createdAt-start]'] = $filter_start;
    }

    if (!empty($filter_end)) {
      $endpoint_options['query']['filter[createdAt-end]'] = $filter_end;
    }

    $response = $this->getResponse($shard, $this->apiEndpointBase, $endpoint_options);

    return $response ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSingleMatch(string $shard, string $match_id) {
    $api_endpoint = "{$this->apiEndpointBase}/{$match_id}";
    $endpoint_options = [];

    $response = $this->getResponse($shard, $api_endpoint, $endpoint_options);

    return $response ?? [];
  }

}
