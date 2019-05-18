<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:59 AM
 */

namespace Drupal\basicshib\Plugin;


use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

interface AuthFilterPluginInterface {
  const ERROR_CREATION_NOT_ALLOWED = 1;
  const ERROR_EXISTING_NOT_ALLOWED = 2;

  /**
   * @return bool
   */
  public function isUserCreationAllowed();

  /**
   * Return an error message based on the type of failure.
   *
   * @param int $code
   *   The reason for the error, i.e. one of:
   *   - ERROR_CREATION_NOT_ALLOWED
   *   - ERROR_EXISTING_NOT_ALLOWED
   *
   * @param UserInterface $account
   *   When $code = ERROR_EXISTING_NOT_ALLOWED, the applicable account is passed
   *   Otherwise, the value is null.
   *
   * @return string
   */
  public function getError($code, UserInterface $account = null);

  /**
   * Determine whether existing user is allowed to log in.
   *
   * Note: The authentication handler checks for blocked accounts, so it is not
   * necessary to do so here.
   *
   * @param UserInterface $account
   * @return bool
   */
  public function isExistingUserLoginAllowed(UserInterface $account);

  /**
   * Check the session. Returns one of the following values:
   *
   * - AuthenticationHandlerInterface::AUTHCHECK_IGNORE
   * - AuthenticationHandlerInterface::AUTHCHECK_REVOKED_BY_PLUGIN
   *
   * It is highly recommended for plugins to log a reason for denial.
   *
   * @param Request $request
   * @param AccountProxyInterface $account
   * @return int
   */
  public function checkSession(Request $request, AccountProxyInterface $account);
}
