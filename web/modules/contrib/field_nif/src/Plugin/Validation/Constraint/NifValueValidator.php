<?php

namespace Drupal\field_nif\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\field_nif\NifUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a NIF/CIF/NIE document number.
 */
class NifValueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $document_value = NifUtils::validateNifCifNie($value, $constraint->getSupportedTypes());

    if (!$document_value) {
      $this->context->addViolation($constraint->getMessage(), [
        '@value' => $value,
        '@document_type' => empty($constraint->getSupportedTypes()) ? 'NIF/CIF/NIE' : Unicode::strtoupper(implode('/', $constraint->getSupportedTypes())),
      ]);
    }
  }

}
