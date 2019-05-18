<?php

namespace Drupal\commerce_recurring_metered_test\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_recurring_metered\Plugin\Commerce\SubscriptionType\MeteredBillingBase;
use Drupal\commerce_recurring_metered\SubscriptionFreeUsageInterface;

/**
 * Test class for usage tracking against a product variation.
 *
 * @CommerceSubscriptionType(
 *  id = "usage_test_product_variation",
 *  label = @Translation("Usage test product variation."),
 *  purchasable_entity_type = "commerce_product_variation",
 * )
 */
class UsageTestProductVariation extends MeteredBillingBase implements SubscriptionFreeUsageInterface {

  /**
   * {@inheritdoc}
   */
  public function getFreeQuantity(ProductVariationInterface $variation, SubscriptionInterface $subscription) {
    if ($variation->getSku() === 'variation_300_free') {
      return 300;
    }

    if ($variation->getSku() === 'variation_5_free') {
      return 5;
    }

    return 0;
  }

}
