<?php

namespace Drupal\commerce_cashpresso\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Defines the cashpresso payment gateway interface.
 */
interface CashpressoGatewayInterface extends OffsitePaymentGatewayInterface {

  /**
   * The cancelled (remote) payment status.
   *
   * @string
   */
  const REMOTE_STATUS_CANCELLED = 'CANCELLED';

  /**
   * The success (remote) payment status.
   *
   * @string
   */
  const REMOTE_STATUS_SUCCESS = 'SUCCESS';

  /**
   * The timeout (remote) payment status.
   *
   * @string
   */
  const REMOTE_STATUS_TIMEOUT = 'TIMEOUT';

  /**
   * Authorizes the given payment.
   *
   * Please note, that payment entity as well as its parent order will be
   * updated and saved afterwards.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param string $cashpresso_token
   *   The cashpresso token.
   *
   * @return bool
   *   TRUE, if authorization was successful, FALSE otherwise.
   */
  public function authorizePayment(PaymentInterface $payment, $cashpresso_token);

  /**
   * Returns the cashpresso endpoint url, respecting active mode (live, test).
   *
   * @return string
   *   The active cashpresso endpoint url as string.
   */
  public function getActiveEndpointUrl();

  /**
   * Generates the verification hash for checkout requests.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return string
   *   The verification hash needed for checkout requests.
   */
  public function generateVerificationHashForCheckoutRequest(PaymentInterface $payment);

  /**
   * Generates the verification hash for verifying success callbacks.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param string $cashpresso_status
   *   The payment status as returned by cashpresso. Possible values are:
   *   ::REMOTE_STATUS_CANCELLED, REMOTE_STATUS_SUCCESS,
   *   ::REMOTE_STATUS_TIMEOUT.
   *
   * @return string
   *   The verification hash needed for verifying success callbacks.
   */
  public function generateVerificationHash(PaymentInterface $payment, $cashpresso_status);

  /**
   * Fetches the partner info from cashpresso.
   *
   * The queried data will be stored for 24 hours, unless the $force_update
   * parameter is set to TRUE.
   *
   * @param bool $force_update
   *   Whether to force update, no matter if the cached data is still valid.
   *   Defaults to FALSE.
   *
   * @return \Drupal\commerce_cashpresso\PartnerInfo|null
   *   The partner info value object. NULL is only returned, when the request
   *   was not successful and we do not have any valid cache data available.
   */
  public function fetchPartnerInfo($force_update = FALSE);

  /**
   * Get the configured API key.
   *
   * @return string
   *   The cashpresso API key.
   */
  public function getApiKey();

  /**
   * Get the number fo interest free days.
   *
   * @return int
   *   The number fo interest free days.
   */
  public function getInterestFreeDaysMerchant();

}
