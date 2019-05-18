<?php

declare(strict_types = 1);

namespace Drupal\erg\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a constraint that requires entity reference guards to pass.
 *
 * @Constraint(
 *   id = "ErgOnValidateRefereeGuardConstraint",
 *   label = @Translation("On reference guards", context = "Validation"),
 *   type = false
 * )
 */
final class OnValidateRefereeGuardConstraint extends Constraint {}
