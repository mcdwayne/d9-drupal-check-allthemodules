<?php

namespace Drupal\social_auth_hid\Settings;

/**
 * Defines an interface for Social Auth Hid settings.
 */
interface HidAuthSettingsInterface {

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
