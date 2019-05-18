<?php

namespace Drupal\bridtv;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Service class of the Brid.TV API.
 */
class BridApiConsumer {

  /**
   * The authorization token.
   *
   * @var string
   */
  protected $authToken;

  /**
   * The partner id.
   *
   * @var string
   */
  protected $partnerId;

  /**
   * Whether the API is available or not.
   *
   * @var bool
   */
  protected $isReady = FALSE;

  /**
   * The Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * BridApiConsumer constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\Client $http_client
   *   The Http client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    $settings = $config_factory->get('bridtv.settings');
    $this->authToken = $settings->get('access_token');
    $this->partnerId = $settings->get('partner_id');
    $this->requestOptions = [
      'headers' => ['Authorization' => 'Bearer ' . $this->authToken],
      'timeout' => 2.0,
      'connect_timeout' => 2.0,
    ];
    $this->httpClient = $http_client;
    $this->isReady = $this->authToken ? TRUE : FALSE;
  }

  /**
   * Whether the API is available or not.
   *
   * @return bool
   */
  public function isReady() {
    return $this->isReady;
  }

  /**
   * Fetch all data for a video by its given id.
   *
   * @param int $id
   *   The video id.
   * @param mixed &$status
   *   (Optional) Use this param to check for the status code.
   *
   * @return string|null
   *   The video data as JSON-encoded string.
   */
  public function fetchVideoData($id, &$status = NULL) {
    $uri = BridApiEndpoints::video($id);
    return $this->doGet($uri, $status);
  }

  /**
   * Get all data, decoded, for a video by its given id.
   *
   * @param int $id
   *   The video id.
   * @param mixed &$status
   *   (Optional) Use this param to check for the status code.
   *
   * @return array|null
   *   The video data as decoded array.
   */
  public function getDecodedVideoData($id, &$status = NULL) {
    return $this->decodeFetched($this->fetchVideoData($id, $status));
  }

  /**
   * Fetch multiple videos as a paginated list.
   *
   * @param int $page
   *   The page to navigate to. Default would be the first page.
   * @param int $limit
   *   The maximum number of videos to fetch. Default is 5 items.
   * @param int $id
   *   (Optional) The site id, which can be the partner_id from settings.
   *
   * @return string|null
   *   The list of videos as JSON-encoded string.
   */
  public function fetchVideosList($page = 1, $limit = 5, $id = NULL) {
    if (!isset($id)) {
      $id = $this->partnerId;
    }
    $uri = BridApiEndpoints::videosList($id, $page, $limit);
    return $this->doGet($uri);
  }

  /**
   * Get multiple videos, decoded, as a paginated list.
   *
   * @param int $page
   *   The page to navigate to. Default would be the first page.
   * @param int $limit
   *   The maximum number of videos to fetch. Default is 5 items.
   * @param int $id
   *   (Optional) The site id, which can be the partner_id from settings.
   *
   * @return array|null
   *   The list of videos as decoded array.
   */
  public function getDecodedVideosList($page = 1, $limit = 5, $id = NULL) {
    return $this->decodeFetched($this->fetchVideosList($page, $limit, $id));
  }

  /**
   * Fetch all available players for the given site id.
   *
   * @param int $id
   *   (Optional) The site id, which can be the partner_id from settings.
   *
   * @return string|null
   *   The list of players as JSON-encoded string.
   */
  public function fetchPlayersList($id = NULL) {
    if (!isset($id)) {
      $id = $this->partnerId;
    }
    $uri = BridApiEndpoints::playersList($id);
    return $this->doGet($uri);
  }

  /**
   * Get all available players, decoded, for the given site id.
   *
   * @param int $id
   *   (Optional) The site id, which can be the partner_id from settings.
   *
   * @return array|null
   *   The list of players as decoded array.
   */
  public function getDecodedPlayersList($id = NULL) {
    return $this->decodeFetched($this->fetchPlayersList($id));
  }

  /**
   * Fetch all available players with data for the given site id.
   *
   * @param int $id
   *   The site id, which can be the partner_id from settings.
   *
   * @return string|null
   *   The list of players with data as JSON-encoded string.
   */
  public function fetchPlayersDataList($id = NULL) {
    if (!isset($id)) {
      $id = $this->partnerId;
    }
    $uri = BridApiEndpoints::playersDataList($id);
    return $this->doGet($uri);
  }

  /**
   * Get all available players with data, decoded, for the given site id.
   *
   * @param int $id
   *   (Optional) The site id, which can be the partner_id from settings.
   *
   * @return array|null
   *   The list of players with data as decoded array.
   */
  public function getDecodedPlayersDataList($id = NULL) {
    return $this->decodeFetched($this->fetchPlayersDataList($id));
  }

  /**
   * Performs a GET request for the given uri.
   *
   * @param string $uri
   *   The uri to GET the response for.
   * @param mixed &$status
   *   (Optional) Use this param to check for the status code.
   *
   * @return null|string
   *   The response content, or NULL in case of an error or not given.
   */
  protected function doGet($uri, &$status = NULL) {
    try {
      $response = $this->httpClient->request('GET', $uri, $this->requestOptions);
      $status = $response->getStatusCode();
      if (($response instanceof ResponseInterface) && ($response->getStatusCode() < 300)) {
        return $response->getBody()->getContents();
      }
    }
    catch (GuzzleException $e) {}
    return NULL;
  }

  /**
   * Decodes the fetched response body.
   *
   * @param null $fetched
   *   The fetched response body as JSON-encoded string.
   *
   * @return array|null
   *   The decoded response body.
   */
  protected function decodeFetched($fetched = NULL) {
    if (isset($fetched)) {
      return BridSerialization::decode($fetched);
    }
    return NULL;
  }

}
