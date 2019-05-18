<?php

namespace Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClient;

use Drupal\openid_connect_rest\Plugin\OpenIDConnectRESTClientTrait;

use Drupal\openid_connect_rest\Plugin\OpenIDConnectClient\Facebook as OpenIDConnectClientFacebook;

/**
 * Facebook OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for Facebook.
 *
 * @OpenIDConnectClient(
 *   id = "facebook",
 *   label = @Translation("Facebook")
 * )
 */
class Facebook extends OpenIDConnectClientFacebook {

  // Overrides OpenIDConnectClientBase::retrieveTokens().
  use OpenIDConnectRESTClientTrait;

}
