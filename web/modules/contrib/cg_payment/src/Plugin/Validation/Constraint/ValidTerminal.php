<?php

namespace Drupal\cg_payment\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the terminal number is valid or not.
 *
 * @Constraint(
 *   id = "ValidTerminal",
 *   label = @Translation("Valid terminal", context = "Validation")
 * )
 */
class ValidTerminal extends Constraint {

  public $message = 'Unable to validate the terminal id and mid against CreditGuard.';

  /**
   * The terminal ID field's name.
   *
   * @var string
   */
  public $terminal_id_field_name;

  /**
   * The merchant ID field's name.
   *
   * @var string
   */
  public $mid_field_name;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return [
      'terminal_id_field_name',
      'mid_field_name',
    ];
  }

}
