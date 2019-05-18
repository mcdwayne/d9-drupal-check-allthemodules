<?php

namespace Drupal\commerce_cost\OrderProcessor;

use Drupal\commerce\Context;
use Drupal\commerce_cost\Resolver\ChainCostResolverInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Class ApplyCost.
 *
 * Sets the cost of order items.
 */
class ApplyCost implements OrderProcessorInterface {

  /**
   * A service for resolving an item's cost.
   *
   * @var \Drupal\commerce_cost\Resolver\ChainCostResolverInterface
   */
  protected $chainCostResolver;

  /**
   * ApplyCost constructor.
   *
   * @param \Drupal\commerce_cost\Resolver\ChainCostResolverInterface $chainCostResolver
   *   A service to determine the cost of an OrderItem.
   */
  public function __construct(ChainCostResolverInterface $chainCostResolver) {
    $this->chainCostResolver = $chainCostResolver;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $context = new Context($order->getCustomer(), $order->getStore());
    foreach ($order->getItems() as $orderItem) {
      if (!empty($orderItem->getPurchasedEntity())) {
        $cost = $this->chainCostResolver->resolve($orderItem->getPurchasedEntity(), $orderItem->getQuantity(), $context);
        if ($orderItem->hasField('field_cost')) {
          $orderItem->set('field_cost', $cost);
        }
      }
    }
  }

}
