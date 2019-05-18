<?php

namespace Drupal\messagebird;

/**
 * Interface MessageBirdLookupInterface.
 *
 * @package Drupal\messagebird
 */
interface MessageBirdLookupInterface {

  /**
   * Look up a telephone number.
   *
   * Checks if the telephone number could be a valid number.
   * This will not check for a active number.
   *
   * @param string $number
   *   Phone number, accepts multiple formats.
   * @param string $country_code
   *   (optional) ISO 3166-2 country code.
   */
  public function lookupNumber($number, $country_code = NULL);

  /**
   * Check if the lookup was a success.
   *
   * @return bool
   *   TRUE on successful look up, FALSE otherwise.
   */
  public function hasValidLookup();

  /**
   * Get URL of lookup info.
   *
   * @return string
   *   URL of lookup info.
   */
  public function getHref();

  /**
   * Get the type of the telephone number..
   *
   * @return string
   *   Type of Telephone number.
   */
  public function getType();

  /**
   * Get the origin country code of the telephone number.
   *
   * @return string
   *   ISO 3166-2 country code
   */
  public function getCountryCode();

  /**
   * Get the country prefix of the telephone number.
   *
   * @return string
   *   Country prefix number.
   */
  public function getCountryPrefix();

  /**
   * Get the telephone number.
   *
   * Useful for numeric storage inside array's.
   *
   * @return int
   *   Telephone number itself without leading zero's
   */
  public function getFormatNumber();

  /**
   * Get the international format of the telephone number.
   *
   * This is the longest format with country prefix and appropriated dashes.
   *
   * @return string
   *   International format of the telephone number.
   */
  public function getFormatInternational();

  /**
   * Get the national format of the telephone number.
   *
   * This format has no country prefix.
   *
   * @return string
   *   National format of the telephone number.
   */
  public function getFormatNational();

  /**
   * Get the e164 format of the telephone number.
   *
   * This format contains only numbers and plus sign.
   *
   * @return string
   *   e164 format of the telephone number.
   */
  public function getFormatE164();

  /**
   * Get the rfc3966 of the telephone number.
   *
   * Useful for creating a telephone URL.
   *
   * @return string
   *   rfc3966 format of the telephone number.
   */
  public function getFormatRfc3966();

}
