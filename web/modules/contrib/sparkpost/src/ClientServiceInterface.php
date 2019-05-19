<?php

namespace Drupal\sparkpost;

/**
 * Interface ClientServiceInterface.
 *
 * @package Drupal\sparkpost
 */
interface ClientServiceInterface {

  /**
   * Gets the client.
   *
   * @return \SparkPost\SparkPost
   *   The client.
   */
  public function getClient();

  /**
   * Sends the message.
   *
   * @param array $message
   *   The sparkpost message.
   *
   * @return array
   *   The body of the response.
   */
  public function sendMessage(array $message);

  /**
   * Sends the request.
   *
   * @param string $endpoint
   *   The endpoint to use.
   * @param array $data
   *   The data to send.
   * @param string $method
   *   The HTTP method to use.
   *
   * @return array
   *   The body of the response.
   */
  public function sendRequest($endpoint, array $data, $method = 'GET');

}
