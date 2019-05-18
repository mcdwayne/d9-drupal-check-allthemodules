<?php

namespace Drupal\commerce_purchase_order\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the customer approved condition for purchase orders.
 *
 * @CommerceCondition(
 *   id = "commerce_purchase_order_auth",
 *   label = @Translation("User Authorized"),
 *   display_label = @Translation("Limit by field: Purchase Orders Authorized"),
 *   category = @Translation("Customer"),
 *   entity_type = "commerce_order",
 * )
 */
class PurchaseOrderCustomerApproved extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $customer = $order->getCustomer();

    return (bool) ($customer->hasField('field_purchase_orders_authorized') && $customer->field_purchase_orders_authorized->first()->value);
  }

}
