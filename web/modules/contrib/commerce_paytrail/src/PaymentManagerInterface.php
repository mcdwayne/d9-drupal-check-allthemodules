<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase;
use Drupal\commerce_paytrail\Repository\FormManager;
use Drupal\commerce_paytrail\Repository\Response;

/**
 * Interface PaymentManagerInterface.
 *
 * @package Drupal\commerce_paytrail
 */
interface PaymentManagerInterface {

  /**
   * Builds form for a given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase $plugin
   *   The payment gateway.
   *
   * @return \Drupal\commerce_paytrail\Repository\FormManager
   *   The form interface.
   */
  public function buildFormInterface(OrderInterface $order, PaytrailBase $plugin) : FormManager;

  /**
   * Dispatches events and generates authcode for a given values.
   *
   * @param \Drupal\commerce_paytrail\Repository\FormManager $form
   *   The form interface.
   * @param \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase $plugin
   *   The payment plugin.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   The generated form values.
   */
  public function dispatch(FormManager $form, PaytrailBase $plugin, OrderInterface $order) : array;

  /**
   * Create new payment for given order.
   *
   * @param string $status
   *   The transaction state (authorized, capture).
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase $plugin
   *   The payment plugin.
   * @param \Drupal\commerce_paytrail\Repository\Response $response
   *   The paytrail response.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment entity.
   */
  public function createPaymentForOrder(string $status, OrderInterface $order, PaytrailBase $plugin, Response $response) : PaymentInterface;

}
