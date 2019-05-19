<?php

namespace Drupal\colorapi\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;

/**
 * Definition class for Typed Data API Color Complex Data types.
 */
class HexColorDataDefinition extends DataDefinition {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // Retrieve the constraint plugin manager.
    $constraint_manager = \Drupal::TypedDataManager()->getValidationConstraintManager();
    // Get any constraints that the parent may have added.
    $constraints = parent::getConstraints();
    // Add a constraint to ensure that submitted data is a valid hexadecimal
    // color string.
    $constraints[] = $constraint_manager->create('HexadecimalColor', []);

    return $constraints;
  }

}
