<?php

namespace Drupal\social_auth_slack\Settings;

/**
 * Defines an interface for Social Auth Slack settings.
 */
interface SlackAuthSettingsInterface {

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
