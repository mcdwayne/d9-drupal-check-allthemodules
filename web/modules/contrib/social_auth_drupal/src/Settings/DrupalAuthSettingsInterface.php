<?php

namespace Drupal\social_auth_drupal\Settings;

/**
 * Defines an interface for Social Auth Drupal settings.
 */
interface DrupalAuthSettingsInterface {

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
   * Gets the Base Url.
   *
   * @return string
   *   The base url.
   */
  public function getBaseUrl();

}
