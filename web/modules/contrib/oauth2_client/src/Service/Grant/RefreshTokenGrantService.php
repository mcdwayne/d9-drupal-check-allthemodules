<?php

namespace Drupal\oauth2_client\Service\Grant;

/**
 * Handles Authorization Grants for the OAuth2 Client module.
 */
class RefreshTokenGrantService extends Oauth2ClientGrantServiceBase {

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($clientId) {
    $accessToken = $this->state->get('oauth2_client_access_token-' . $clientId);
    if ($accessToken && $accessToken->getExpires() && $accessToken->hasExpired()) {
      $provider = $this->getProvider($clientId);
      $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $accessToken->getRefreshToken(),
      ]);

      $this->storeAccessToken($clientId, $newAccessToken);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantProvider($clientId) {
    return $this->getProvider($clientId);
  }

}
