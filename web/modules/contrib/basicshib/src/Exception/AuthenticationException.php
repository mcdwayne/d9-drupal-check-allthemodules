<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/16/17
 * Time: 9:58 AM
 */

namespace Drupal\basicshib\Exception;


class AuthenticationException extends BasicShibException {
  const UNCLASSIFIED_ERROR = 0;
  const MISSING_ATTRIBUTES = 1;
  const USER_CREATION_NOT_ALLOWED = 2;
  const USER_CREATION_FAILED = 4;
  const USER_UPDATE_FAILED = 8;
  const USER_BLOCKED = 16;
  const USER_FINALIZE_FAILED = 32;
  const LOGIN_DISALLOWED_FOR_USER = 64;
}
