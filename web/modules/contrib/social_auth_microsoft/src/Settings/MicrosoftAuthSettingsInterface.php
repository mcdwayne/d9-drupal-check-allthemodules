<?php

namespace Drupal\social_auth_microsoft\Settings;

/**
 * Defines an interface for Social Auth Microsoft settings.
 */
interface MicrosoftAuthSettingsInterface {

  /**
   * Gets the App ID.
   *
   * @return string
   *   The app ID.
   */
  public function getAppId();

  /**
   * Gets the App secret.
   *
   * @return string
   *   The app secret.
   */
  public function getAppSecret();

}
