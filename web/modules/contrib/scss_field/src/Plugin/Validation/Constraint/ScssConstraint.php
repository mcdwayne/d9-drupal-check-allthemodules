<?php

namespace Drupal\scss_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted text is valid SCSS.
 *
 * @Constraint(
 *   id = "Scss",
 *   label = @Translation("SCSS", context = "Validation"),
 * )
 */
class ScssConstraint extends Constraint {
  /**
   * The message that will be shown if the submitted value is not valid SCSS.
   *
   * @var string
   */
  public $invalidScss = 'The text submitted is not valid SCSS.\n%error';

}
