<?php

namespace Drupal\gapi\Plugin;

use \Google_Client;

/**
 * Provides a Google API Service plugin manager.
 *
 * @see \Drupal\gapi\GoogleApiServiceProviderManager
 * @see plugin_api
 */
interface GoogleApiServiceProviderManagerInterface {

  /**
   * Sets the authenticated client to use with the service providers.
   *
   * @param \Google_Client $client
   *   The authenticated Google_Client
   */
  public function setClient(Google_Client $client);

  /**
   * Creates a new api service client with an authenticated client.
   *
   * @param string $service_id
   *   The service client requested.
   * @param array $configuration
   *   Any additional configuration for the service provider.
   *
   * @return object
   *   The requested service client with an authenticated client.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *    If the service provider cannot be created, such as if the ID is invalid.
   */
  public function getService($service_id, array $configuration = []);

}
