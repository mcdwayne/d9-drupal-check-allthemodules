<?php

/**
 * @file
 * Contains \Drupal\themekey\Plugin\Operator\Equals.
 */

namespace Drupal\themekey\Plugin\Operator;

use Drupal\themekey\OperatorBase;

/**
 * Provides an 'equals' operator.
 *
 * @Operator(
 *   id = "=",
 *   name = @Translation("Equals"),
 *   description = @Translation("Equals (exact value of a property, consider ThemeKey Debug to get an impression of the possible values)"),
 * )
 */
class Equals extends OperatorBase {

  /**
   * @return bool
   *   TRUE if $value1 == $value2
   */
  public function evaluate($value1, $value2) {
    return ($value1 == $value2);
  }

}
