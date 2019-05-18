<?php

namespace Drupal\oauth2_client\Service;

/**
 * Interface for the OAuth2 Client service.
 */
interface Oauth2ClientServiceInterface {

  /**
   * Retrieve an OAuth2 Client Plugin.
   *
   * @param string $clientId
   *   The plugin ID of the client to be retrieved.
   *
   * @return \Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginInterface
   *   The OAuth2 Client plugin.
   */
  public function getClient($clientId);

  /**
   * Retrieve an access token from the Drupal state.
   *
   * @param string $clientId
   *   The client for which a provider should be created.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   The Access Token for the given client ID.
   */
  public function retrieveAccessToken($clientId);

  /**
   * Clears the access token for the given client.
   *
   * @param string $clientId
   *   The client for which a provider should be created.
   */
  public function clearAccessToken($clientId);

}
