<?php

namespace Drupal\monster_menus\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Checks certain MM-specific node fields.
 *
 * @Constraint(
 *   id = "MMNodeValidation",
 *   label = @Translation("MM Node validation constraint.", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class MMNodeValidationConstraint extends CompositeConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['mm_catlist', 'mm_catlist_restricted', 'id'];
  }

}
