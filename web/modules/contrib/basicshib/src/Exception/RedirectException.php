<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/13/17
 * Time: 3:32 PM
 */

namespace Drupal\basicshib\Exception;


class RedirectException extends BasicShibException {
  const BLOCKED_EXTERNAL = 1;
  const INVALID_PATH = 2;
}
