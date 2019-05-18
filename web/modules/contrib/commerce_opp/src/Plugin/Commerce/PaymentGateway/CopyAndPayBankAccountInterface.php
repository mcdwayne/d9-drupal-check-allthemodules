<?php

namespace Drupal\commerce_opp\Plugin\Commerce\PaymentGateway;

/**
 * Provides the interface for Open Payment Platform bank payment gateways.
 */
interface CopyAndPayBankAccountInterface extends CopyAndPayInterface {

  /**
   * Returns a list of all configured SOFORT Überweisung countries.
   *
   * The list is tailored for use with the COPYandPAY widget, so that the keys
   * will be the country codes, and the values are the shown labels.
   *
   * @return string[]
   *   A list of all currently configured SOFORT Überweisung countries.
   */
  public function getSofortCountries();

  /**
   * Returns whether SOFORT countries should be restricted on billing address.
   *
   * The base for the selection will be the selected SOFORT countries, which
   * will be further restricted to the billing address.
   *
   * @return bool
   *   TRUE, if SOFORT countries should be restricted on billing address. FALSE
   *   otherwise.
   */
  public function isSofortRestrictedToBillingAddress();

}
