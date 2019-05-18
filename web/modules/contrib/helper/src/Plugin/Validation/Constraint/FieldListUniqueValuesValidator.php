<?php

namespace Drupal\helper\Plugin\Validation\Constraint;

use Drupal\helper\Field;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates duplicate field values.
 */
class FieldListUniqueValuesValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $value
   * @param \Drupal\helper\Plugin\Validation\Constraint\FieldListUniqueValues $constraint
   */
  public function validate($value, Constraint $constraint) {
    // If the field is empty or doesn't have more than one value, there is
    // nothing to validate.
    if (!isset($value) || count($value) <= 1) {
      return;
    }

    if ($duplicates = Field::getDuplicateValues($value, $constraint->property)) {
      if ($constraint->show_values) {
        $this->context->addViolation($constraint->messageWithValues, [
          '%field_name' => $value->getFieldDefinition()->getLabel(),
          '@values' => implode(', ', $duplicates),
        ]);
      }
      else {
        $this->context->addViolation($constraint->message, [
          '%field_name' => $value->getFieldDefinition()->getLabel(),
        ]);
      }
    }
  }

}
