<?php

namespace Drupal\social_post_linkedin\Plugin\Network;

use Drupal\social_post\Plugin\Network\SocialPostNetworkInterface;

/**
 * Defines the LinkedIn Post interface.
 */
interface LinkedInPostInterface extends SocialPostNetworkInterface {

  /**
   * Wrapper for post method.
   *
   * @param string $access_token
   *   The access token.
   * @param string $status
   *   The tweet text.
   */
  public function doPost($access_token, $status);

}
