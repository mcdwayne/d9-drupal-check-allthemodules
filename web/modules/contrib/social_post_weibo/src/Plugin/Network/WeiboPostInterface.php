<?php

namespace Drupal\social_post_weibo\Plugin\Network;

use Drupal\social_post\Plugin\Network\SocialPostNetworkInterface;

/**
 * Defines an interface for Weibo Post Network Plugin.
 */
interface WeiboPostInterface extends SocialPostNetworkInterface {

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
   * @param string $access_token_secret
   *   The access token secret.
   * @param string $status
   *   The tweet text.
   */
  public function doPost($access_token, $status, $weibo_post_parms);

  /**
   * Gets a WeiboOAuth instance with oauth_token and oauth_token_secret.
   *
   * This method creates the SDK object by also passing the oauth_token and
   * oauth_token_secret. It is used for getting permanent tokens from
   * Weibo and authenticating users that has already granted permission.
   *
   * @param string $access_token
   *   The access token.
   *
   */
  public function getSdk2($access_token);

}
