<?php

namespace Drupal\address_dawa\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Address DAWA constraint.
 *
 * @Constraint(
 *   id = "AddressDawa",
 *   label = @Translation("Address DAWA validation", context = "Validation"),
 *   type = { "address_dawa" }
 * )
 */
class AddressDawaConstraint extends Constraint {

  /**
   * Address can not be resolved to an actual location via DAWA service.
   *
   * @var array
   */
  const ADDRESS_CAN_NOT_BE_FOUND = [
    'error_code' => 1,
    'message' => 'Address can not be found @address.',
  ];

  /**
   * Address resolved to multiple locations.
   *
   * @var array
   */
  const ADDRESS_MULTIPLE_LOCATION = [
    'error_code' => 2,
    'message' => 'Address resolved to multiple locations @address.',
  ];

  /**
   * Address is different from the configured type.
   *
   * @var array
   */
  const ADDRESS_INVALID_TYPE = [
    'error_code' => 3,
    'message' => 'Please provide "@correct_type" address. You have entered "@wrong_type" address.',
  ];

}
