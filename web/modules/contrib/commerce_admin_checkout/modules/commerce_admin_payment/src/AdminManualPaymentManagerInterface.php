<?php

namespace Drupal\commerce_admin_payment;


use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

interface AdminManualPaymentManagerInterface {

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway\AdminManualPaymentGatewayInterface[]
   */
  public function getAdminManualPaymentGateways(OrderInterface $order);

  /**
   * @param string $payment_gateway_id
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null
   */
  public function loadPaymentGateway($payment_gateway_id);

  
}
