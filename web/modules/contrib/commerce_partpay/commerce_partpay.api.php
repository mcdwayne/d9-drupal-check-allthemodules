<?php

/**
 * @file
 * Developer documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow other modules to alter partpay payment capture.
 *
 * @param array $data
 *   The payment data being save to commerce.
 * @param \Drupal\commerce_order\Entity\OrderInterface $order
 *   The order.
 * @param \Omnipay\Common\Message\ResponseInterface $response
 *   The payment gateway response.
 */
function hook_commerce_partpay_capture_payment_alter(
  array &$data,
  \Drupal\commerce_order\Entity\OrderInterface $order,
  \Omnipay\Common\Message\ResponseInterface $response
) {
  $data['state'] = 'my_custom_state';
}

/**
 * @} End of "addtogroup hooks".
 */
