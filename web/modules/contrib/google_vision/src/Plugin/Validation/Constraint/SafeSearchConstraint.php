<?php

namespace Drupal\google_vision\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Constraint(
 *   id = "SafeSearch",
 *   label = @Translation("Safe Search", context = "Validation"),
 * )
 */
class SafeSearchConstraint extends Constraint {

  public $message = 'This image contains explicit content and will not be saved.';
}
