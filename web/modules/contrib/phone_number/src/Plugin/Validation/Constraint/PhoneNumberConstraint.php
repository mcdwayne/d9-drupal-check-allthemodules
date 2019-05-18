<?php

namespace Drupal\phone_number\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates Phone number fields.
 *
 * Includes validation for:
 *   - Number validity.
 *   - Allowed country.
 *   - Uniqueness.
 *
 * @Constraint(
 *   id = "PhoneNumber",
 *   label = @Translation("Phone Number constraint", context = "Validation"),
 * )
 */
class PhoneNumberConstraint extends Constraint {

  public $unique = 'A @entity_type with @field_name @value already exists.';
  public $validity = 'The @field_name @value is invalid for the following reason: @message.';
  public $allowedCountry = 'The country @value provided for @field_name is not an allowed country.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\phone_number\Plugin\Validation\Constraint\PhoneNumberValidator';
  }

}
