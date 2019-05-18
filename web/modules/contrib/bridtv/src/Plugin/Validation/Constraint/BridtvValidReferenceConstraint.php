<?php

namespace Drupal\bridtv\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint;

/**
 * Brid.TV valid reference constraint.
 *
 * @Constraint(
 *   id = "BridtvValidReference",
 *   label = @Translation("Brid.TV valid reference", context = "Validation")
 * )
 */
class BridtvValidReferenceConstraint extends ValidReferenceConstraint {

}
