<?php

namespace Drupal\cck_select_other\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Choice;

/**
 * Overrides AllowedValuesConstraint.
 *
 * This exists simply to override AllowedValuesConstraint plugin, which is a
 * poor method of field validation that is not extendable in any way for field
 * widgets.
 *
 * Core plugins are "internal", which means any code inside a plugin cannot be
 * relied upon at all because of the backwards-incompatibility policy, and all
 * code in core plugins needs to be preserved to maintain backwards-
 * compatibility.
 */
class SelectOtherAllowedValuesConstraint extends Choice {

  public $minMessage = 'You must select at least %limit choice.|You must select at least %limit choices.';
  public $maxMessage = 'You must select at most %limit choice.|You must select at most %limit choices.';

}
