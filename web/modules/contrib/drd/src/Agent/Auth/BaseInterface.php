<?php

namespace Drupal\drd\Agent\Auth;

/**
 * Interface for authentication methods.
 */
interface BaseInterface {

  /**
   * Get a list of all implemented authentication methods.
   *
   * @var int $version
   *   Main Drupal core version, between and including 6 to 8.
   *
   * @return array
   *   Array of all implemented authentication methods.
   */
  public static function getMethods($version);

  /**
   * Verify if the given UUID is authorised to access this site.
   *
   * @param string $uuid
   *   UUID of the authentication object that should be validated.
   *
   * @return bool
   *   TRUE if authenticated, FALSE otherwise.
   */
  public function validateUuid($uuid);

  /**
   * Validate authentication of the current request with the given settings.
   *
   * @param array $settings
   *   Authentication settings from the request.
   *
   * @return bool
   *   TRUE if authenticated, FALSE otherwise.
   */
  public function validate(array $settings);

}
