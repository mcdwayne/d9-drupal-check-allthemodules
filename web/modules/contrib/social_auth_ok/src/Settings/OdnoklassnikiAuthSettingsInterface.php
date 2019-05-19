<?php

namespace Drupal\social_auth_ok\Settings;

/**
 * Defines the settings interface.
 */
interface OdnoklassnikiAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return mixed
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the client public key
   *
   * @return string
   *   The client public key
   */
  public function getClientPublic();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

}
