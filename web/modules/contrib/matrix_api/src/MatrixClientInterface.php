<?php

namespace Drupal\matrix_api;

/**
 * Interface MatrixClientInterface.
 *
 * @package Drupal\matrix_api
 */
interface MatrixClientInterface {

  /**
   * Sets up an http client with the configured matrix endpoint and access token.
   *
   * @return \GuzzleHttp\Client
   */
  public static function gethttp();

  /**
   * Generic GET request on Matrix Client API.
   *
   * @param string $path
   * @param array|null $query
   *   - query parameters to pass.
   *
   * @return array
   */
  public function get($path, array $query = []);

  /**
   * Generic POST request on Matrix Client API.
   *
   * @param string $path
   * @param array $data
   *
   * @return array
   */
  public function post($path, array $data);

  /**
   * Generic PUT request on Matrix Client API.
   *
   * @param string $path
   * @param array $data
   *
   * @return array
   */
  public function put($path, array $data);

  /**
   * Generic DELETE request on Matrix Client API.
   *
   * @param string $path
   *
   * @return array
   */
  public function delete($path);

  /**
   * Execute a Matrix Sync and return all data as a nested array.
   *
   * @param array $options
   *
   * @return array
   */
  public function sync(array $options = []);

  /**
   * Call the login endpoint with username, password.
   * Returns an array, with the "access_token" set to the token to use for future requests.
   *
   * @param string $user
   * @param string $password
   *
   * @return string
   *   access_token
   */
  public function login($user, $password);

  /**
   * Joins a room by either an alias or the Room Id.
   *
   * @param string $roomAliasOrId
   * @param array|null $third_party_signed
   *
   * @return string RoomID
   */
  public function join($roomAliasOrId, array $third_party_signed = NULL);

  /**
   * Leaves a room, or decline an invite.
   *
   * @param $room
   *   - Either the roomId, or a room object
   *
   * @return bool indicating successful leave
   */
  public function leave($room);

  /**
   * Retrieve messages from room.
   *
   * @param string|object $room
   *   - roomId or room object.
   * @param array|null $options
   *   - Query options such as pagination.
   *
   * @return array
   */
  public function messages($room, array $options = []);

  /**
   * Send a message to a room.
   *
   * @param string|object $room
   *   - roomId or room object.
   * @param string|array $body
   *   - if string, set as the body type. Otherwise PUT as is.
   * @param array $options
   *   - eventType, txnId may be set/overridden here.
   *
   * @return string event_id returned by Matrix
   */
  public function sendMessage($room, $body, array $options = []);

  /**
   * Get a particular state for a room, with the identified stateKey..
   *
   * @param string|object $room
   *   - roomId or room object.
   * @param string $eventType
   * @param string|null $stateKey
   *
   * @return mixed False if state not found, otherwise array of state value(s).
   */
  public function getState($room, $eventType, $stateKey = '');

  /**
   * Set a state in a room.
   *
   * @param string|object $room
   *   - roomId or room object.
   * @param string $eventType
   * @param string $stateKey
   * @param array $state
   *
   * @return string event_id returned by Matrix
   */
  public function setState($room, $eventType, $stateKey, array $state);

}
