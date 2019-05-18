<?php

namespace Drupal\address_dawa\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks that the referenced DAWA address is of the configured type.
 */
class AddressDawaConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $field_value = $value->getValue();
    if ($field_value['type'] === AddressDawaConstraint::ADDRESS_CAN_NOT_BE_FOUND['error_code']) {
      $this->context
        ->buildViolation(AddressDawaConstraint::ADDRESS_CAN_NOT_BE_FOUND['message'])
        ->atPath('value')
        ->setParameter('@address', $value->getTextValue())
        ->addViolation();
      return;
    }

    if ($field_value['type'] === AddressDawaConstraint::ADDRESS_MULTIPLE_LOCATION['error_code']) {
      $this->context
        ->buildViolation(AddressDawaConstraint::ADDRESS_MULTIPLE_LOCATION['message'])
        ->atPath('value')
        ->setParameter('@address', $value->getTextValue())
        ->addViolation();
      return;
    }

    $address_type = $value->getFieldDefinition()->getSetting('address_type');
    if ($address_type !== $field_value['type']) {
      $this->context
        ->buildViolation(AddressDawaConstraint::ADDRESS_INVALID_TYPE['message'])
        ->atPath('value')
        ->setParameters([
          '@correct_type' => ucwords($address_type),
          '@wrong_type' => ucwords($field_value['type']),
        ])
        ->addViolation();
    }
  }

}
