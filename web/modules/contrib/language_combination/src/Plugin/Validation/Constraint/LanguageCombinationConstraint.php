<?php

namespace Drupal\language_combination\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Constraint(
 *   id = "LanguageCombination",
 *   label = @Translation("Language Combination", context = "Validation"),
 * )
 */
class LanguageCombinationConstraint extends Constraint {

  public $noDifferentMessage = 'The \'from\' and \'to\' language fields can\'t have the same value.';
  public $uniqueMessage = 'The language combination has to be unique.';

}
