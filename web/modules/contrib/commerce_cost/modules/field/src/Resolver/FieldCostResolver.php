<?php

namespace Drupal\commerce_cost_field\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cost\Resolver\CostResolverInterface;

/**
 * Returns the price based on the purchasable entity's cost field.
 *
 * @package Drupal\commerce_cost\Resolver
 */
class FieldCostResolver implements CostResolverInterface {

  /**
   * The machine name of the cost field.
   */
  const COSTFIELD = 'field_cost';

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    if (!$entity || !$entity->hasField(static::COSTFIELD)) {
      return NULL;
    }
    $cost = $entity->get(static::COSTFIELD);
    if ($cost->isEmpty()) {
      return NULL;
    }

    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $cost */
    $cost = $cost->get(0);
    return $cost->toPrice()->multiply($quantity);
  }

}
