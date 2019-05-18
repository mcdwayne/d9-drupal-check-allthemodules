<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\Entity\OrderInterface;

interface CheckoutSdkInterface {

  /**
   * Gets an access token.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getAccessToken();

  /**
   * Creates an order in PayPal.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function createOrder(OrderInterface $order);

  /**
   * Get an existing order from PayPal.
   *
   * @param $remote_id
   *   The PayPal order ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getOrder($remote_id);

  /**
   * Updates an existing PayPal order.
   *
   * @param $remote_id
   *   The PayPal order ID.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function updateOrder($remote_id, OrderInterface $order);

  /**
   * Authorize payment for order.
   *
   * @param $remote_id
   *   The PayPal order ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function authorizeOrder($remote_id);

  /**
   * Capture payment for order.
   *
   * @param $remote_id
   *   The PayPal order ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function captureOrder($remote_id);

  /**
   * Captures an authorized payment, by ID.
   *
   * @param $authorization_id
   *   The PayPal-generated ID for the authorized payment to capture.
   * @param array $parameters
   *   (optional An array of parameters to pass as the request body.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function capturePayment($authorization_id, array $parameters = []);

  /**
   * Reauthorizes an authorized PayPal account payment, by ID.
   *
   * @param $authorization_id
   *   The PayPal-generated ID of the authorized payment to reauthorize.
   * @param array $parameters
   *   (optional An array of parameters to pass as the request body.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function reAuthorizePayment($authorization_id, array $parameters = []);

  /**
   * Refunds a captured payment, by ID.
   *
   * @param $capture_id
   *   The PayPal-generated ID for the captured payment to refund.
   * @param array $parameters
   *   (optional An array of parameters to pass as the request body.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function refundPayment($capture_id, array $parameters = []);

  /**
   * Voids, or cancels, an authorized payment, by ID.
   *
   * @param $authorization_id
   *   The PayPal-generated ID of the authorized payment to void.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function voidPayment($authorization_id);

}
