<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/12/17
 * Time: 9:35 AM
 */

namespace Drupal\basicshib\Exception;


class AttributeException extends BasicShibException {
  const NOT_MAPPED = 1;
  const NOT_SET = 2;
  const DUPLICATE_ID = 3;
}
