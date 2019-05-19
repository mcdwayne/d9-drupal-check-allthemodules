<?php

namespace Drupal\social_auth_twitch\Settings;

/**
 * Defines an interface for Social Auth Twitch settings.
 */
interface TwitchAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return string
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

}
