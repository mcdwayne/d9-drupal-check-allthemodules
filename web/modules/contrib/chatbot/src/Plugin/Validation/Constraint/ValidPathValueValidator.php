<?php

namespace Drupal\chatbot\Plugin\Validation\Constraint;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field value is a valid path.
 */
class ValidPathValueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    $value = $item->value;

    $value_valid = UrlHelper::isValid($value);

    if (!$value_valid) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@field_name' => $items->getFieldDefinition()->getLabel(),
      ]);
    }
  }

}
