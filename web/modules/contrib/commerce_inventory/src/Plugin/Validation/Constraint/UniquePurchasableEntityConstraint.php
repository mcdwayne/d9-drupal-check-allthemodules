<?php

namespace Drupal\commerce_inventory\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field has a unique value.
 *
 * @Constraint(
 *   id = "UniquePurchasableEntity",
 *   label = @Translation("Unique purchasable entity per location.", context = "Validation"),
 * )
 */
class UniquePurchasableEntityConstraint extends Constraint {

  public $message = [
    'empty' => 'Valid purchasable item and location required.',
    'required_location' => 'A valid location is required.',
    'required_purchasable_entity' => 'A valid purchasable item is required.',
    'exists' => 'This purchasable item already exists at this location.',
    'exists_context' => '%purchasable_entity already exists at the location %location.',
  ];

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\commerce_inventory\Plugin\Validation\Constraint\UniquePurchasableEntityValidator';
  }

}
