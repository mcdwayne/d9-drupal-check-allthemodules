<?php

namespace Drupal\commerce_cashpresso;

/**
 * Defines the partner info store interface.
 *
 * Stores and returns partner info from cashpresso.
 */
interface PartnerInfoStoreInterface {

  /**
   * Returns the stored partner info, if available and valid.
   *
   * The partner info is only stored/cached for a certain amount of time. So
   * the caller should expect NULL value as return to be common.
   *
   * @param string $merchant_id
   *   An optional merchant ID to store. By default there's none.
   *
   * @return \Drupal\commerce_cashpresso\PartnerInfo|null
   *   The stored partner info as value object, if available and valid. NULL,
   *   otherwise.
   */
  public function getPartnerInfo($merchant_id = '');

  /**
   * Clears stored partner info - e.g. on saving the payment gateway config.
   *
   * @param string $merchant_id
   *   An optional merchant ID to store. By default there's none.
   */
  public function clearPartnerInfo($merchant_id = '');

  /**
   * Stores the given partner info for an expirable amount of time.
   *
   * @param \Drupal\commerce_cashpresso\PartnerInfo $partner_info
   *   The partner info.
   * @param string $merchant_id
   *   An optional merchant ID to store. By default there's none.
   */
  public function setPartnerInfo(PartnerInfo $partner_info, $merchant_id = '');

}
