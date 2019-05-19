<?php

namespace Drupal\social_post_facebook\Settings;

/**
 * Defines an interface for Social Post Facebook settings.
 */
interface FacebookPostSettingsInterface {

  /**
   * Gets the application ID.
   *
   * @return mixed
   *   The application ID.
   */
  public function getAppId();

  /**
   * Gets the application secret.
   *
   * @return string
   *   The application secret.
   */
  public function getAppSecret();

  /**
   * Gets the graph version.
   *
   * @return string
   *   The version.
   */
  public function getGraphVersion();

}
