<?php

namespace Drupal\micro_sso;

/**
 * Middleware for the micro_site module.
 */
interface MicroSsoHelperInterface {

  /**
   * The request is done on the master host.
   *
   * @return bool
   *   Return TRUE if the host is the master host.
   */
  public function isMaster();

  /**
   * Get a valid HTTP Origin URL if it matches an existing site.
   *
   * @return string|Null
   *   The existing site url if found, else Null.
   */
  public function getOrigin();

  /**
   * Write token and return it with the login uri.
   *
   * @param string $origin
   *   The http origin base url.
   *
   * @return array
   *   An array keyed by
   *     - uri : valid URL for login into the site.
   *     - token : the token generated
   *     - destination : a destination parameter (optional)
   */
  public function writeToken($origin);

  /**
   * Get default URL scheme.
   */
  public function getScheme();

  /**
   * Get the current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request oject.
   */
  public function getRequest();

  /**
   * Get the Request Time.
   *
   * @return string
   *   The request time timestamp.
   */
  public function getRequestTime();

  /**
   * Get the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user object.
   */
  public function getCurrentUser();

  /**
   * The current user is authenticated ?
   *
   * @return bool
   *   TRUE if the current user is authenticated.
   */
  public function userIsAuthenticated();

  /**
   * Get the sso cache backend object.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The micro SSO cache backend.
   */
  public function getCacheSso();

}
