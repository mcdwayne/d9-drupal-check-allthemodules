<?php

namespace Drupal\social_post_linkedin\Settings;

/**
 * Defines an interface for Social Post LinkedIn settings.
 */
interface LinkedInPostSettingsInterface {

  /**
   * Gets the application ID.
   *
   * @return mixed
   *   The application ID.
   */
  public function getClientId();

  /**
   * Gets the application secret.
   *
   * @return string
   *   The application secret.
   */
  public function getClientSecret();

}
