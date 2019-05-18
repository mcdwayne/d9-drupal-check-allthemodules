<?php

/**
 * @file
 * Hooks specific to the Commerce ePayco module.
 */

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter ePayco data before sending it to the Gateway.
 */
function hook_commerce_epayco_payment_data(Order $order, PaymentInterface $payment, array $parameters) {
  $parameters['p_cust_id_cliente'] = '0';
  $parameters['p_key'] = '0';
  $parameters['p_test_request'] = TRUE;

  return $parameters;
}

/**
 * @} End of "addtogroup hooks".
 */
