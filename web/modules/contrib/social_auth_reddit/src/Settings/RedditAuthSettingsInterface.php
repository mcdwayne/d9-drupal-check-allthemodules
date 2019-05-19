<?php

namespace Drupal\social_auth_reddit\Settings;

/**
 * Defines an interface for Social Auth Reddit settings.
 */
interface RedditAuthSettingsInterface {

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

  /**
   * Gets the data Point defined the settings form page.
   *
   * @return string
   *   Comma-separated scopes.
   */
  public function getScopes();

  /**
   * Gets the User Agent string input in settings form page.
   *
   * @return string
   *   User agent string.
   */
  public function getUserAgentString();

}
