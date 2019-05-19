<?php

namespace Drupal\social_auth_pbs\Settings;

use Drupal\social_auth\Settings\SettingsInterface;

/**
 * Defines an interface for Social Auth PBS settings.
 */
interface PbsAuthSettingsInterface extends SettingsInterface {

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
