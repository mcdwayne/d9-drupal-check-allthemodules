<?php

namespace Drupal\social_post_facebook\Plugin\Network;

use Drupal\social_post\Plugin\Network\SocialPostNetworkInterface;

/**
 * Defines an interface for Facebook Post Network Plugin.
 */
interface FacebookPostInterface extends SocialPostNetworkInterface {

  /**
   * Gets the absolute url of the callback.
   *
   * @return string
   *   The callback url.
   */
  public function getOauthCallback();

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
