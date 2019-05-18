<?php

namespace Drupal\commerce_opp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Open Payment Platform payment gateway.
 */
interface CopyAndPayInterface extends OffsitePaymentGatewayInterface, SupportsRefundsInterface {

  /**
   * The default live environment host.
   *
   * @var string
   */
  const DEFAULT_HOST_LIVE = 'https://oppwa.com';

  /**
   * The default test environment host.
   *
   * @var string
   */
  const DEFAULT_HOST_TEST = 'https://test.oppwa.com';

  /**
   * Prepares the checkout by fetching a checkout ID from the payment gateway.
   *
   * @param string[] $params
   *   The parameters for initializing a payment. Should at least contain
   *   'paymentType', 'amount', 'currency' and 'desriptor'.
   *
   * @return string
   *   A valid checkout ID as provided by Open Payment Platform gateway.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   If there's no checkout ID returned, or a request error occurred.
   */
  public function prepareCheckout(array $params = []);

  /**
   * Fetches the transaction status for the given checkout ID.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity (having the checkout ID set as remote ID).
   *
   * @return \Drupal\commerce_opp\Transaction\Status\TransactionStatusInterface
   *   The transaction status.
   */
  public function getTransactionStatus(PaymentInterface $payment);

  /**
   * Returns the active gateway url, depending on the active mode (test, live).
   *
   * @return string
   *   The active gateway url. The active mode (test, live) is considered.
   */
  public function getActiveHostUrl();

  /**
   * Returns the configured gateway url for live mode.
   *
   * @return string
   *   The configured gateway url for live mode.
   */
  public function getLiveHostUrl();

  /**
   * Returns the configured gateway url for test mode.
   *
   * @return string
   *   The configured gateway url for test mode.
   */
  public function getTestHostUrl();

  /**
   * Returns a list of all currently configured brand IDs.
   *
   * @return string[]
   *   A list of all currently configured brand IDs.
   */
  public function getBrandIds();

  /**
   * Returns a list of all currently configured brands.
   *
   * @return \Drupal\commerce_opp\Brand[]
   *   A list of all currently configured brands.
   */
  public function getBrands();

  /**
   * Returns whether to show the amount to be paid on the payment page.
   *
   * @return bool
   *   TRUE, if the gateway is configured to show the payable amount on the
   *   payment page, FALSE ohterwise.
   */
  public function isAmountVisible();

  /**
   * Calculates the expiration time of a checkout ID based on a given timestamp.
   *
   * @param int|null $request_time
   *   A timestamp, or NULL. If NULL, the current request timestamp will be
   *   used.
   *
   * @return int
   *   The expiration timestamp.
   */
  public function calculateCheckoutIdExpireTime($request_time = NULL);

  /**
   * Returns the payable amount for the given order.
   *
   * By default, this is exactly the order total, ensuring a decimal precision
   * of exact two digits, as this is required by Open Payment Platform.
   *
   * Further, it gives other modules the possibility to alter this price, e.g.
   * it allows custom modules to provide early payment discounts, that do not
   * change the original order total value.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\commerce_price\Price
   *   The calculated payment amount to charge.
   */
  public function getPayableAmount(OrderInterface $order);

}
