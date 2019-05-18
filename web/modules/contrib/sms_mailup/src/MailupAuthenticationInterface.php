<?php

namespace Drupal\sms_mailup;

/**
 * The MailUp authentication service interface.
 */
interface MailupAuthenticationInterface {

  /**
   * Get OAuth provider for a gateway.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   *
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   *   The OAuth provider for a gateway.
   *
   * @throws \Exception
   *   If missing required configuration details.
   */
  public function createOAuthProvider($gateway_id);

  /**
   * Sets the OAuth state.
   *
   * This will void existing token details because the state will only match
   * against a new OAuth token request.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   * @param string $state
   *   The OAuth state.
   */
  public function setState($gateway_id, $state);

  /**
   * Get the token for a gateway.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   * @param bool $refresh
   *   Refreshes the token if it has expired.
   *
   * @return \League\OAuth2\Client\Token\AccessToken|FALSE
   *   An OAuth token, or FALSE if a token has not been initialised.
   */
  public function getToken($gateway_id, $refresh = TRUE);

  /**
   * Set a token for a gateway.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   * @param $state
   *   State to protect from CSRF.
   * @param $access_token
   * @param $refresh_token
   * @param $expiration
   *
   * @return mixed
   *
   * @throws \Exception
   */
  public function setToken($gateway_id, $state, $access_token, $refresh_token, $expiration);

  /**
   * Deletes a token for a gateway.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   */
  public function removeToken($gateway_id);

  /**
   * Forces a token to expire for a gateway.
   *
   * @param string $gateway_id
   *   A gateway entity ID.
   */
  public function expireToken($gateway_id);

}
