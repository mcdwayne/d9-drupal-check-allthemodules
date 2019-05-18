<?php

namespace Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClient;

use Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientTrait;

use Drupal\openid_connect\Plugin\OpenIDConnectClient\Generic as OpenIDConnectClientGeneric;

/**
 * Generic OpenID Connect client.
 *
 * Used primarily to login to Drupal sites powered by oauth2_server or PHP
 * sites powered by oauth2-server-php.
 *
 * @OpenIDConnectClient(
 *   id = "generic",
 *   label = @Translation("Generic")
 * )
 */
class Generic extends OpenIDConnectClientGeneric {

  // Overrides OpenIDConnectClientBase::retrieveTokens().
  use OpenIDConnectRESTClientTrait;

}
