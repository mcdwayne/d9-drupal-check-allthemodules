<?php

namespace Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClient;

use Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientTrait;

use Drupal\openid_connect\Plugin\OpenIDConnectClient\Linkedin as OpenIDConnectClientLinkedin;

/**
 * Linkedin OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for Linkedin.
 *
 * @OpenIDConnectClient(
 *   id = "linkedin",
 *   label = @Translation("Linkedin")
 * )
 */
class Linkedin extends OpenIDConnectClientLinkedin {

  // Overrides OpenIDConnectClientBase::retrieveTokens().
  use OpenIDConnectRESTClientTrait;

}
