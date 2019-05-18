<?php

namespace Drupal\gapi\Plugin;

use \Google_Client;

interface GoogleApiServiceProviderInterface {

  /**
   * Returns a service with an authenticated client.
   *
   * @param \Google_Client $client
   *   The authenticated client.
   *
   * @return object
   *   The specific service.
   */
  public function getService(Google_Client $client);

}
