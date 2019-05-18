<?php

namespace Drupal\commerce_opp\Transaction\Status;

/**
 * Defines the transaction status interface.
 */
interface TransactionStatusInterface {

  /**
   * Gets the transaction ID.
   *
   * @return string
   *   The transaction ID.
   */
  public function getId();

  /**
   * Gets the result code.
   *
   * @return string
   *   The result code.
   */
  public function getCode();

  /**
   * Gets the status description.
   *
   * @return string
   *   The status description.
   */
  public function getDescription();

  /**
   * Gets the type of this status.
   *
   * The result is one of the TYPE_* constants of the Constants class. As the
   * class of the given instance already implies the status type, this can be
   * seen as redundant information for convenience.
   *
   * @return string
   *   The status type (whether if the transaction was successful, failed, etc).
   */
  public function getType();

  /**
   * Gets the payment brand.
   *
   * @return \Drupal\commerce_opp\Brand|null
   *   The payment brand.
   */
  public function getBrand();

  /**
   * Gets whether or not the payment is async.
   *
   * It always depends on the brand, if the payment type is sync or async.
   *
   * @return bool
   *   TRUE, if the associated payment brand is an async one, FALSE otherwise.
   */
  public function isAsyncPayment();

}
