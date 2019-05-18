<?php

namespace Drupal\commerce_cost\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the interface for base cost resolvers.
 */
interface CostResolverInterface {

  /**
   * Resolves the cost of a given purchasable entity or order item.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_price\Price|null
   *   A price value object, if resolved. Otherwise NULL, indicating that the
   *   next resolver in the chain should be called.
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context);

}
