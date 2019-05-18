<?php
/**
 * @file
 * Contains Drupal\block_render\Immutable.
 */

namespace Drupal\block_render;


/**
 * A set of libraries.
 */
abstract class Immutable {

  /**
   * Prevent properties from being set.
   *
   * @param string $name
   *   Property name.
   * @param mixed $value
   *   Value of the property.
   */
  public function __set($name, $value) {
    throw new \LogicException('You cannot set properties.');
  }

}
