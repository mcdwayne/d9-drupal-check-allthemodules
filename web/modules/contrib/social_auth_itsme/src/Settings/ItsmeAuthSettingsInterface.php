<?php

namespace Drupal\social_auth_itsme\Settings;

/**
 * Defines methods to get Social Auth itsme settings.
 */
interface ItsmeAuthSettingsInterface {

  /**
   * Gets the token.
   *
   * @return string
   *   The token.
   */
  public function getToken();

  /**
   * Gets the scopes.
   *
   * @return array
   *   The scopes.
   */
  public function getScopes();

  /**
   * Gets the service.
   *
   * @return string
   *   The service.
   */
  public function getService();

}
