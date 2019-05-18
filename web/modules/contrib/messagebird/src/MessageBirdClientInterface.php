<?php

namespace Drupal\messagebird;

/**
 * Interface MessageBirdClientInterface.
 *
 * @package Drupal\messagebird
 */
interface MessageBirdClientInterface {

  /**
   * Get an authenticated Client connection with MessageBird.
   *
   * @param string|null $api_key
   *   (optional) The API-key supplied by MessageBird.
   *
   * @return \MessageBird\Client
   *   The Client object.
   */
  public function getClient($api_key = NULL);

}
