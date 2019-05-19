<?php

namespace Drupal\simple_integrations;

/**
 * Custom implementation of the Drupal HTTP client.
 *
 * Returns a new connection client, which acts as an extension of core's
 * http_client service.
 */
class ConnectionClientFactory {

  /**
   * Return a Connection client.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   A connection client.
   */
  public function get() {
    return new ConnectionClient();
  }

}
