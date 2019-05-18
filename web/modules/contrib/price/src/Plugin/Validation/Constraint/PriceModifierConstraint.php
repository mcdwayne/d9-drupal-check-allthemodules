<?php

namespace Drupal\price\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * PriceModifier constraint.
 *
 * @Constraint(
 *   id = "PriceModifier",
 *   label = @Translation("Price Modifier", context = "Validation"),
 *   type = {
 *     "price_modified"
 *   }
 * )
 */
class PriceModifierConstraint extends Constraint {

  public $availableModifiers = [];
  public $invalidMessage = 'The modifier %value is not valid.';
  public $notAvailableMessage = 'The modifier %value is not available.';

}
