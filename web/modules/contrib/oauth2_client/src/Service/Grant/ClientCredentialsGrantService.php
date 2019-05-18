<?php

namespace Drupal\oauth2_client\Service\Grant;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Handles Authorization Grants for the OAuth2 Client module.
 */
class ClientCredentialsGrantService extends Oauth2ClientGrantServiceBase {

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($clientId) {
    $provider = $this->getProvider($clientId);

    try {
      $accessToken = $provider->getAccessToken('client_credentials');

      $this->storeAccessToken($clientId, $accessToken);
    }
    catch (IdentityProviderException $e) {
      // Failed to get the access token.
      watchdog_exception('OAuth2 Client', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantProvider($clientId) {
    return $this->getProvider($clientId);
  }

}
