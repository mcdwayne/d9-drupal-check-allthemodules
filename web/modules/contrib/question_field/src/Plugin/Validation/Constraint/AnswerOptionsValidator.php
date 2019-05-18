<?php

namespace Drupal\question_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TimeslotRegistrationsExists constraint.
 */
class AnswerOptionsValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * Validates that the string is in the form:
   *
   * value1|text1|follow-up-question
   * value2|text2
   * ...
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\question_field\Plugin\Validation\Constraint\AnswerOptions $constraint */
    $answers = explode("\n", $items);
    foreach ($answers as $answer) {
      $answer = trim($answer);
      if ($answer) {
        $options = explode('|', $answer);
        $count = count($options);
        if ($count == 3) {
          // @todo: validate that $options[2] is a valid question.
        }
        elseif ($count == 1 || $count > 3) {
          $this->context->addViolation($constraint->invalidCount);
        }
      }
    }
  }

}
