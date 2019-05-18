<?php

namespace Drupal\commerce_quantity_pricing\Resolvers;

use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\Context;

/**
 * Class PriceResolver.
 *
 * @package Drupal\commerce_quantity_pricing\Resolvers
 */
class PriceResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $entities = $entity->referencedEntities();

    // Extract product from entity so we can grab the taxonomy.
    /** @var \Drupal\commerce_product\Entity\Product $item */
    $product = NULL;
    foreach ($entities as $item) {
      if ($item->getEntityTypeId() === 'commerce_product') {
        $product = $item;
      }
    }
    $quantity_price = commerce_quantity_pricing_get_price($product, $quantity);
    return $quantity_price ?? $entity->getPrice();
  }

}
