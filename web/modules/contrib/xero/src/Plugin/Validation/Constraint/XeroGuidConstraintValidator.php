<?php

namespace Drupal\xero\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provide simple regular expression to validate guid.
 */
class XeroGuidConstraintValidator extends ConstraintValidator {

  /**
   * Implements \Symfony\Component\Validator\ConstraintValidatorInterface::validate().
   */
  public function validate($value, Constraint $constraint) {

    if (!isset($value)) {
      return;
    }

    $guid_regex = '/^\{?[A-Fa-f0-9]{8}-(?:[A-Fa-f0-9]{4}-){3}[A-Fa-f0-9]{12}\}?$/';
    $valid = TRUE;

    if (!preg_match($guid_regex, $value)) {
      $valid = FALSE;
    }

    if (!$valid) {
      $this->context->addViolation($constraint->message, array(
        '%value' => is_object($value) ? get_class($value) : (is_array($value) ? 'Array' : (string) $value)
      ));
    }
  }
}
