<?php

/**
 * @file
 * Contains \Drupal\saudi_identity_field\Plugin\Validation\Constraint\SaudiIdentityCheckConstraint.
 */

namespace Drupal\saudi_identity_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Constraint for saudi identity check.
 *
 * @Constraint(
 *   id = "SaudiIdentityCheck",
 *   label = @Translation("Saudi identity check", context = "Validation"),
 *   type = { "Integer", "entity:node" }
 * )
 */
class SaudiIdentityCheckConstraint extends Constraint {

}
