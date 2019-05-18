<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\Diff.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Entrance point and utility function for creating diff objects.
 */
class Diff {

  /**
   * Diffs two arbitrary values and returns various info about the delta.
   *
   * @param mixed $val1
   *  The first value to diff.
   * @param mixed $val2
   *  The second value to diff.
   * @param string $name
   *  A name for the variable represented in the diff.
   *
   * @return DiffInterface
   *  An array with information about the diff.
   */
  public static function diff($val1, $val2) {
    if (gettype($val1) !== gettype($val2)) {
      return new TypeChange($val1, $val2);
    }

    switch (gettype($val1)) {
      case 'array':
        return new ArrayDiff($val1, $val2);
      case 'object':
        return new ObjectDiff($val1, $val2);
      case 'double':
        return new FloatDiff($val1, $val2);
      case 'integer':
        return new IntegerDiff($val1, $val2);
      case 'boolean':
        return new BooleanDiff($val1, $val2);
      case 'resource':
        return new ResourceDiff($val1, $val2);
      default:
        return new NullDiff($val1, $val2);
    }
  }

  /**
   * Returns a human-readable string val representing the given type.
   *
   * @param mixed $val
   *
   * @return string
   */
  public static function getTypeString($val) {
    switch ($t = gettype($val)) {
      case 'double':
        // Try to keep people from being confused by the old 'double' return val
        return 'float';

      case 'object':
        return get_class($val);

      default:
        return $t;
    }
  }

}