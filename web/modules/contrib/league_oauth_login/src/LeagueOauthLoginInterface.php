<?php

namespace Drupal\league_oauth_login;

use Drupal\Component\Plugin\PluginInspectionInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Interface definition for league_oauth_login plugins.
 */
interface LeagueOauthLoginInterface extends PluginInspectionInterface {

  /**
   * Gets a provider.
   *
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   *   The provider.
   */
  public function getProvider();

  /**
   * What options do we need.
   *
   * @return array
   *   The options we need to get the auth url.
   */
  public function getAuthUrlOptions();

  /**
   * Get a username to use for registering users.
   *
   * @return string
   *   A usable username.
   */
  public function getUserName(ResourceOwnerInterface $owner);

  /**
   * Get the email.
   *
   * @return string
   *   Hopefully an email.
   */
  public function getEmail(ResourceOwnerInterface $owner, $access_token);

}
