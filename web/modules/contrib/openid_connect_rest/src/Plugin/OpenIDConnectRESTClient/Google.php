<?php

namespace Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClient;

use Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientTrait;

use Drupal\openid_connect\Plugin\OpenIDConnectClient\Google as OpenIDConnectClientGoogle;

/**
 * Google OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for Google.
 *
 * @OpenIDConnectClient(
 *   id = "google",
 *   label = @Translation("Google")
 * )
 */
class Google extends OpenIDConnectClientGoogle {

  // Overrides OpenIDConnectClientBase::retrieveTokens().
  use OpenIDConnectRESTClientTrait;

}
