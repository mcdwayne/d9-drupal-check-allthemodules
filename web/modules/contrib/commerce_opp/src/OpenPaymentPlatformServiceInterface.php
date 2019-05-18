<?php

namespace Drupal\commerce_opp;

/**
 * Defines the Open Payment Platform service interface.
 */
interface OpenPaymentPlatformServiceInterface {

  /**
   * Returns a list of configured Open Payment Platform gateway IDs.
   *
   * @param bool $only_active
   *   Whether to only return active gateways. Defaults to TRUE.
   *
   * @return string[]
   *   An array of configured Open Payment Platform gateway IDs.
   */
  public function getOppGatewayIds($only_active = TRUE);

  /**
   * Deletes expired Open Payment Platform authorizations.
   *
   * Every time, the COPYandPAY is selected on checkout and the payment page is
   * visited then, a new payment entity with state "authorization" is created
   * and saved. If the customer does not complete the payment, or is uncertain
   * and jumps back and forth in the checkout process, a lot of unneeded data
   * will be generated and stored then. This service does the cleanup work and
   * deletes expired Open Payment Platform authorizations.
   */
  public function deleteExpiredAuthorizations();

  /**
   * Processes pending authorizations.
   *
   * Open Payment Platform does not actively send notification callbacks as many
   * other gateways do. Instead they only send the checkout ID alongside calling
   * the return URL. This means, if for whatever reason the customer doesn't get
   * redirected back to the payment return page, we'll never get notified about
   * the transaction status, leading to unplaced and locked orders, although the
   * payment was most likely successful.
   *
   * Therefore we need to proactively fetch the transaction status for every
   * pending authorization on cron run by ourselves.
   */
  public function processPendingAuthorizations();

}
