<?php

namespace Drupal\commerce_opp;

/**
 * Defines constants for the different payment types.
 */
final class PaymentTypes {

  /**
   * Preauthorization type.
   *
   * A stand-alone authorisation that will also trigger optional risk management
   * and validation. A Capture (CP) with reference to the Preauthorisation (PA)
   * will confirm the payment.
   */
  const PREAUTHORIZATION = 'PA';

  /**
   * Debit type.
   *
   * Debits the account of the end customer and credits the merchant account.
   */
  const DEBIT = 'DB';

  /**
   * Credit type.
   *
   * Credits the account of the end customer and debits the merchant account.
   */
  const CREDIT = 'CD';

  /**
   * Capture type.
   *
   * Captures a preauthorized (PA) amount.
   */
  const CAPTURE = 'CP';

  /**
   * Reversal type.
   *
   * Reverses an already processed Preauthorization (PA), Debit (DB) or Credit
   * (CD) transaction. As a consequence, the end customer will never see any
   * booking on his statement. A Reversal is only possible until a connector
   * specific cut-off time. Some connectors don’t support Reversals.
   */
  const REVERSAL = 'RV';

  /**
   * Refund type.
   *
   * Credits the account of the end customer with a reference to a prior Debit
   * (DB) or Credit (CD) transaction. The end customer will always see two
   * bookings on his statement. Some connectors do not support refunds.
   */
  const REFUND = 'RF';

}
