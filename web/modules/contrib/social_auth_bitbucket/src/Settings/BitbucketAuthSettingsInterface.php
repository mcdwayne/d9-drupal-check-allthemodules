<?php

namespace Drupal\social_auth_bitbucket\Settings;

/**
 * Defines an interface for Social Auth Bitbucket settings.
 */
interface BitbucketAuthSettingsInterface {

  /**
   * Gets the key.
   *
   * @return string
   *   The key.
   */
  public function getKey();

  /**
   * Gets the secret.
   *
   * @return string
   *   The secret.
   */
  public function getSecret();

}
