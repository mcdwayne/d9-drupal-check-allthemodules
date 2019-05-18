<?php

namespace Drupal\matrix_api;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;

/**
 * Class MatrixClient.
 *
 * @package Drupal\matrix_api
 */
class MatrixClient implements MatrixClientInterface {

  /**
   * Matrix API version string to send.
   */
  const API_VERSION = 'r0';

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var string
   */
  private $access_token;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * MatrixClient constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->logger = $logger_factory;
    $this->httpClient = self::gethttp();
    $this->access_token = \Drupal::config('matrix_api.MatrixApiSettings')->get('matrix_api.token');
  }

  /**
   * Return a configured Guzzle Client object.
   */
  public static function gethttp() {
    $token = \Drupal::config('matrix_api.MatrixApiSettings')->get('matrix_api.token');
    $config = [
      'base_uri' => \Drupal::config('matrix_api.MatrixApiSettings')->get('matrix_api.home_server_url')
      . '/_matrix/client/' . self::API_VERSION . '/',
    ];

    if ($token) {
      $config['default']['headers']['Authorization'] = 'Bearer ' . $token;
    }

    return new Client($config);
  }

  /**
   * @inheritdoc
   */
  public function get($path, array $query = []) {
    $headers['Authorization'] = 'Bearer ' . $this->access_token;
    $options['headers'] = $headers;
    $request = $this->httpClient->get($path, $options);
    return json_decode($request->getBody());
  }

  /**
   * @inheritdoc
   */
  public function post($path, array $data) {
    $headers = [
      'Authorization' => 'Bearer ' . $this->access_token,
    ];
    $request = $this->httpClient->post($path,
        [
          'json' => $data,
          'headers' => $headers,
        ]
      );
    return json_decode($request->getBody());
  }

  /**
   * @inheritDoc
   */
  public function put($path, array $data) {
    $headers = [
      'Authorization' => 'Bearer ' . $this->access_token,
    ];
    $request = $this->httpClient->put($path,
      [
        'json' => $data,
        'headers' => $headers,
      ]
    );
    return json_decode($request->getBody());
  }

  /**
   * @inheritDoc
   */
  public function delete($path) {
    $headers['Authorization'] = 'Bearer ' . $this->access_token;
    $options['headers'] = $headers;
    $request = $this->httpClient->delete($path, $options);
    return json_decode($request->getBody());
  }

  /**
   * @inheritDoc
   */
  public function sync(array $options = []) {
    if (!isset($options['full_state'])) {
      $options['full_state'] = TRUE;
    }

    $response = $this->get('sync', $options);

    return $response;
  }

  /**
   * @inheritDoc
   *
   * Failure codes:
   *
   * 400 - bad request
   * 403 - failed
   * 429 - rate limited
   */
  public function login($user, $password) {
    $query = [
      'type' => 'm.login.password',
      'user' => $user,
      'password' => $password,
    ];

    $response = $this->post('login', $query);
    $token = $response->access_token;
    $this->access_token = $token;
    return $token;
  }

  /**
   * @inheritDoc
   */
  public function join($roomAliasOrId, array $third_party_signed = NULL) {
    $roomAliasOrId = urlencode($roomAliasOrId);
    $response = $this->post('join/' . $roomAliasOrId, []);
    return $response->room_id;
  }

  /**
   * @inheritDoc
   */
  public function leave($room) {
    $roomId = is_object($room) ? $room->roomId : $room;
    return $this->post('rooms/' . $roomId . '/leave', []);
  }

  /**
   * @inheritDoc
   */
  public function messages($room, array $options = []) {
    // TODO: Implement messages() method.
  }

  /**
   * @inheritDoc
   */
  public function sendMessage($room, $body, array $options = []) {
    $roomId = is_object($room) ? $room->roomId : $room;

    if (is_string($body)) {
      $body = [
        'body' => $body,
        'msgtype' => 'm.notice',
      ];
    }
    $eventType = isset($options['eventType']) ? $options['eventType'] : 'm.room.message';
    $txnId = uniqid();
    $response = $this->put('rooms/' . $roomId . '/send/' . $eventType . '/' . $txnId, $body);
    return $response;
  }

  /**
   * @inheritDoc
   */
  public function getState($room, $eventType, $stateKey = '') {
    // TODO: Implement getState() method.
  }

  /**
   * @inheritDoc
   */
  public function setState($room, $eventType, $stateKey, array $state) {
    // TODO: Implement setState() method.
  }

}
