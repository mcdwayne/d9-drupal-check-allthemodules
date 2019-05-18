<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 11:14 AM
 */

namespace Drupal\basicshib;

use Drupal\basicshib\Exception\AttributeException;

/**
 * Get an attribute's value.
 *
 * @param $id
 *   The id of the attribute to fetch.  An exception is thrown if no mapping
 *   exists for the provided id.
 *
 * @param bool $empty_allowed
 *   Whether to allow empty attributes. When false, an exception is thrown if
 *   the attribute is not set.
 *
 * @return string
 *   The value of the attribute
 *
 * @throws AttributeException
 */
interface AttributeMapperInterface {
  public function getAttribute($id, $empty_allowed = false);
}
