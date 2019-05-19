<?php

namespace Drupal\social_auth_unsplash\Settings;

/**
 * Defines an interface for Social Auth Unsplash settings.
 */
interface UnsplashAuthSettingsInterface {

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
