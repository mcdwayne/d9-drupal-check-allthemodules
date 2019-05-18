<?php

namespace Drupal\sharedemail\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserMailUnique;

/**
 * Checks if a user's email address should and is unique on the site.
 *
 * @Constraint(
 *   id = "SharedEmailUnique",
 *   label = @Translation("Shared email unique", context = "Validation")
 * )
 */
class SharedEmailUnique extends UserMailUnique {

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return SharedEmailUniqueValidator::class;
  }

}
