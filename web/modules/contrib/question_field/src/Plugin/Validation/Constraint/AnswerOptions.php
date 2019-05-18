<?php

namespace Drupal\question_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a answer options are in the correct format.
 *
 * @Constraint(
 *   id = "AnswerOptions",
 *   label = @Translation("Answer options", context = "Validation"),
 * )
 */
class AnswerOptions extends Constraint {

  public $invalidCount = 'The answer options are not properly formatted. Enter each answer on a newline. Separate the value and textual string with a |.';

}
