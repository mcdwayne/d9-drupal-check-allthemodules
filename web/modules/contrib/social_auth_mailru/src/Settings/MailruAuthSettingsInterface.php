<?php

namespace Drupal\social_auth_mailru\Settings;

/**
 * Defines an interface for Social Auth Mailru settings.
 */
interface MailruAuthSettingsInterface {

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
