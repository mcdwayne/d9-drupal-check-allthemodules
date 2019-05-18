<?php

namespace Drupal\mobile_number\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates Mobile number fields. Includes validation for:
 *   - Number validity
 *   - Allowed country
 *   - Verification flood
 *   - Mobile number verification
 *   - Uniqueness.
 *
 * @Constraint(
 *   id = "MobileNumber",
 *   label = @Translation("Mobile number constraint", context = "Validation"),
 * )
 */
class MobileNumberConstraint extends Constraint {

  public $verifyRequired = 'The @field_name @value must be verified.';
  public $unique = 'A @entity_type with @field_name @value already exists.';
  public $validity = 'The @field_name @value is invalid for the following reason: @message.';
  public $flood = 'Too many verification attempts for @field_name @value, please try again in a few hours.';
  public $verification = 'Invalid verification code for @field_name @value.';
  public $allowedCountry = 'The country @value provided for @field_name is not an allowed country.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\mobile_number\Plugin\Validation\Constraint\MobileNumberValidator';
  }

}
