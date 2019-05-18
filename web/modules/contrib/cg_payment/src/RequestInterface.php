<?php

namespace Drupal\cg_payment;

/**
 * Interface RequestInterface.
 *
 * @package Drupal\cg_payment
 */
interface RequestInterface {

  /**
   * Request CreditGuard to charge token (make the actual payment).
   *
   * @todo Move some of the params to a different class.
   *
   * @param string $txId
   *   The transaction ID.
   * @param string $token
   *   The token to charge.
   * @param string $cardExp
   *   The card expiration date.
   * @param string $terminalNumber
   *   The terminal number.
   * @param string $mid
   *   The marchant ID.
   * @param float $amount
   *   The amount to charge.
   *
   * @return bool|\Drupal\cg_payment\TransactionInterface
   *   The transaction or false on error.
   */
  public function requestChargeToken($txId, $token, $cardExp, $terminalNumber, $mid, $amount);

  /**
   * Request payment form url from CreditGuard.
   *
   * @param string $terminalNumber
   *   Terminal number.
   * @param string $mid
   *   Merchant id.
   * @param float $amount
   *   Total amount to charge in Agorot (e.g. 100 = 1 NIS).
   * @param string $email
   *   Email of the paying user.
   * @param string $description
   *   Short description to be displayed in the payment form.
   *
   * @return string|null
   *   Returns the redirect URL or null on error.
   */
  public function requestPaymentFormUrl($terminalNumber, $mid, $amount, $email, $description);

}
