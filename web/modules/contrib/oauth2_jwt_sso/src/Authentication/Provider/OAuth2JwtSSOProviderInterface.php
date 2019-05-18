<?php
namespace Drupal\oauth2_jwt_sso\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface OAuth2JwtSSOProviderInterface
 *
 * @package Drupal\oauth2_jwt_sso\Authentication\Provider
 */
interface OAuth2JwtSSOProviderInterface extends AuthenticationProviderInterface {

  /**
   * Checks whether bearer tokens are on the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if bearer tokens are on the request, FALSE otherwise.
   */
  public static function hasToken(Request $request);
}
