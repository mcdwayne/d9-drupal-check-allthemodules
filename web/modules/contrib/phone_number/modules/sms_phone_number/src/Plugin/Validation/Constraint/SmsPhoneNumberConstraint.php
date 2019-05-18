<?php

namespace Drupal\sms_phone_number\Plugin\Validation\Constraint;

use Drupal\phone_number\Plugin\Validation\Constraint\PhoneNumberConstraint;

/**
 * Validates SMS Phone Number fields.
 *
 * Includes validation for:
 *   - Number validity.
 *   - Allowed country.
 *   - Uniqueness.
 *   - Verification flood.
 *   - Phone number verification.
 *
 * @Constraint(
 *   id = "SmsPhoneNumber",
 *   label = @Translation("SMS Phone Number constraint", context = "Validation"),
 * )
 */
class SmsPhoneNumberConstraint extends PhoneNumberConstraint {

  public $flood = 'Too many verification attempts for @field_name @value, please try again in a few hours.';
  public $verification = 'Invalid verification code for @field_name @value.';
  public $verifyRequired = 'The @field_name @value must be verified.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\sms_phone_number\Plugin\Validation\Constraint\SmsPhoneNumberValidator';
  }

}
