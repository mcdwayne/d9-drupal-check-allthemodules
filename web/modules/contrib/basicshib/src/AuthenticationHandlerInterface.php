<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/16/17
 * Time: 9:39 AM
 */

namespace Drupal\basicshib;

use Drupal\basicshib\Exception\AuthenticationException;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;

interface AuthenticationHandlerInterface {
  const AUTHCHECK_IGNORE = 0;
  const AUTHCHECK_LOCAL_SESSION_EXPIRED = 2;
  const AUTHCHECK_SHIB_SESSION_EXPIRED = 3;
  const AUTHCHECK_SHIB_SESSION_ID_MISMATCH = 4;
  const AUTHCHECK_REVOKED_BY_PLUGIN = 5;

  /**
   * Attempt to authenticate.
   *
   * @throws AuthenticationException
   */
  public function authenticate();

  /**
   * @param Request $request
   * @return mixed
   */
  public function checkUserSession(Request $request, AccountProxyInterface $account);

  /**
   * @return string
   */
  public function getLoginUrl();
}
