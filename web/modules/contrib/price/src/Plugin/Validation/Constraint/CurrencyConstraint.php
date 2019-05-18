<?php

namespace Drupal\price\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Currency constraint.
 *
 * @Constraint(
 *   id = "Currency",
 *   label = @Translation("Currency", context = "Validation"),
 *   type = {
 *     "price_price",
 *     "price_modified"
 *   }
 * )
 */
class CurrencyConstraint extends Constraint {

  public $availableCurrencies = [];
  public $invalidMessage = 'The currency %value is not valid.';
  public $notAvailableMessage = 'The currency %value is not available.';

}
