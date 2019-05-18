<?php

namespace Drupal\commerce_avatax\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Resolves tax code based on product variation value.
 */
class ProductVariationTaxCodeResolver implements TaxCodeResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();
    if ($purchased_entity instanceof ProductVariationInterface) {
      if (!$purchased_entity->get('avatax_tax_code')->isEmpty()) {
        return $purchased_entity->get('avatax_tax_code')->value;
      }
    }
  }

}
