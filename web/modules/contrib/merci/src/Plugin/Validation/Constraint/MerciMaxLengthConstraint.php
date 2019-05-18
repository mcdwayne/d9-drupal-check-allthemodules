<?php

/**
 * @file
 * Contains \Drupal\merci\Plugin\Validation\Constraint\MerciMaxLengthConstraint.
 */

namespace Drupal\merci\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Constraint(
 *   id = "MerciMaxLength",
 *   label = @Translation("MerciMaxLength", context = "Validation"),
 * )
 */
class MerciMaxLengthConstraint extends Constraint {
  public $date_field;

  public $interval_field;

  public $grouping_field;

  public $message = 'Item cannot be reserved for longer than @interval @period.';
  /**
    ** {@inheritdoc}
    */
  public function getRequiredOptions() {
    return array('date_field', 'interval_field');
  }
}
