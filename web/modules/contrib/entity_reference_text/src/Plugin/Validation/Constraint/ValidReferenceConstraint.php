<?php

namespace Drupal\entity_reference_text\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint as BaseValidReferenceConstraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced text entities are valid.
 *
 * @Constraint(
 *   id = "EntityReferenceTextValidReference",
 *   label = @Translation("Entity Reference text valid reference", context = "Validation")
 * )
 */
class ValidReferenceConstraint extends BaseValidReferenceConstraint {

}
