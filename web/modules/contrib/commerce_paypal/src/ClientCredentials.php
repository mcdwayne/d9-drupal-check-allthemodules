<?php

namespace Drupal\commerce_paypal;

use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials as BaseClientCredentials;

/**
 * Client credentials grant type.
 */
class ClientCredentials extends BaseClientCredentials {

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    $token = parent::getToken();

    // Store the token retrieved for later reuse (to make sure we don't request
    // for a new one on each API request).
    \Drupal::state()->set('commerce_paypal.oauth2_token', [
      'token' => $token->getToken(),
      'expires' => $token->getExpires()->getTimestamp(),
    ]);

    return $token;
  }

}
