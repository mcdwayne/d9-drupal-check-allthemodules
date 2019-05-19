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
 *   id = "<",
 *   name = @Translation("Lower"),
 *   description = @Translation("TODO"),
 * )
 */
class Lower extends OperatorBase {

  /**
   * @return bool
   *   TRUE if $value1 < $value2
   */
  public function evaluate($value1, $value2) {
    return ($value1 < $value2);
  }
}
