<?php

namespace Drupal\commerce_adyen\Adyen\Transaction;

/**
 * Adyen refund transaction.
 */
class Refund extends Payment {

  /**
   * {@inheritdoc}
   */
  protected function load($remote_status) {
    $entity_type = 'commerce_payment_transaction';

    // We must use "\EntityFieldQuery" because for "amount", conditional
    // operator is required.
    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);
    $query->propertyCondition('amount', 0, '<');
    $query->propertyCondition('order_id', $this->getOrder()->order_id->value());
    $query->propertyCondition('instance_id', $this->getPaymentInstanceId());
    $query->propertyCondition('payment_method', $this->getPaymentMethodName());

    if (!empty($remote_status)) {
      $query->propertyCondition('remote_status', $remote_status);
    }

    $results = $query->execute();

    return empty($results[$entity_type]) ? FALSE : commerce_payment_transaction_load(key($results[$entity_type]));
  }

}
