<?php

/**
 * @file
 * Contains \Drupal\chessboard\Plugin\Validation\Constraint\RegexConstraint.
 */

namespace Drupal\chessboard\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Regex as Regex;

/**
 * Regex constraint.
 *
 * @Constraint(
 *   id = "ChessboardRegex",
 *   label = @Translation("Regex", context = "Validation"),
 *   type = { "string" }
 * )
 */
class RegexConstraint extends Regex {

  /**
   * Overrides Regex::validatedBy().
   */
  public function validatedBy() {
    return '\Symfony\Component\Validator\Constraints\RegexValidator';
  }

}
