<?php

namespace Drupal\sourcepoint\Api;

/**
 * Interface ClientInterface.
 *
 * @package Drupal\sourcepoint\Api
 */
interface ClientInterface {

  /**
   * Set the client API key.
   *
   * @param string $api_key
   *   API key.
   *
   * @return ClientInterface
   *   Client service.
   */
  public function setApiKey($api_key);

  /**
   * Requests provided URL.
   *
   * @param string $url
   *   URL to fetch.
   *
   * @return string
   *   Response body.
   */
  public function request($url);

}
