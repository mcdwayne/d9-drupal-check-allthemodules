<?php

namespace Drupal\persian_fields\Plugin\Validation\Constraint;


use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "iran_card_number",
 *   label = @Translation("IranCardNumber", context = "Validation"),
 *   type = "string"
 * )
 */
class IranCardNumber extends Constraint {

  public static $message = 'This value is not a valid card number.';
}