<?php

namespace Drupal\phone_number;

use libphonenumber\PhoneNumber;

/**
 * The Phone Number field utility interface.
 */
interface PhoneNumberUtilInterface {

  const PHONE_NUMBER_UNIQUE_NO = 0;
  const PHONE_NUMBER_UNIQUE_YES = 1;

  /**
   * Get libphonenumber Util instance.
   *
   * @return \libphonenumber\PhoneNumberUtil
   *   Libphonenumber utility instance.
   */
  public function libUtil();

  /**
   * Get a phone number object.
   *
   * @param string $number
   *   Number.
   * @param null|string $country
   *   Country.
   * @param null|string $extension
   *   Extension.
   *
   * @return \libphonenumber\PhoneNumber|null
   *   Phone Number object if successful.
   */
  public function getPhoneNumber($number, $country = NULL, $extension = NULL);

  /**
   * Test phone number validity.
   *
   * @param string $number
   *   Number.
   * @param null|string $country
   *   (Optional) Country.
   * @param null|string $extension
   *   (Optional) Extension.
   * @param null|array $types
   *   (Optional) An array of allowed PhoneNumberType constants.
   *   Only consider number valid if it is one of these types.
   *   See \libphonenumber\PhoneNumberType for available type contants.
   *
   * @throws \Drupal\phone_number\Exception\CountryException
   *   Thrown if phone number is not valid because its country and the country
   *   provided do not match.
   * @throws \Drupal\phone_number\Exception\ParseException
   *   Thrown if phone number could not be parsed, and is thus invalid.
   * @throws \Drupal\phone_number\Exception\TypeException
   *   Thrown if phone number is an invalid type.
   *
   * @return \libphonenumber\PhoneNumber
   *   Libphonenumber Phone number object.
   */
  public function testPhoneNumber($number, $country = NULL, $extension = NULL, $types = NULL);

  /**
   * Get country code.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   *
   * @return string
   *   Country code.
   */
  public function getCountry(PhoneNumber $phone_number);

  /**
   * Get country display name given country code.
   *
   * @param string $country
   *   Country code.
   *
   * @return string
   *   Country name.
   */
  public function getCountryName($country);

  /**
   * Get callable number.
   *
   * Callable number is an E.164-formatted, international number.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param bool $strip_non_digits
   *   Strip non-digits from the callable number.  Optioinal, defaults to FALSE.
   * @param bool $strip_extension
   *   Strip extension from the callable number.  Optioinal, defaults to TRUE.
   *
   * @return string
   *   An E.164-formatted, international number.
   */
  public function getCallableNumber(PhoneNumber $phone_number, $strip_non_digits = FALSE, $strip_extension = TRUE);

  /**
   * Get national dialing prefix.
   *
   * National dialing prefix is used for certain types of numbers in some
   * regions.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param bool $strip_non_digits
   *   Strip non-digits from the national dialing prefix.  Optioinal, defaults
   *   to FALSE.
   *
   * @return string|null
   *   National dialing prefix.
   */
  public function getNationalDialingPrefix(PhoneNumber $phone_number, $strip_non_digits = FALSE);

  /**
   * Get national number.
   *
   * National number is the National (significant) Number as defined in ITU
   * Recommendation E.164.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param bool $strip_non_digits
   *   Strip non-digits from the national number.  Optioinal, defaults to FALSE.
   * @param bool $strip_extension
   *   Strip extension from the national number.  Optioinal, defaults to TRUE.
   *
   * @return string
   *   National number.
   */
  public function getNationalNumber(PhoneNumber $phone_number, $strip_non_digits = FALSE, $strip_extension = TRUE);

  /**
   * Get local number.
   *
   * Local number is the national number with the national dialling prefix
   * prepended when required/appropriate.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param bool $strip_non_digits
   *   Strip non-digits from the local number.  Optioinal, defaults to FALSE.
   * @param bool $strip_extension
   *   Strip extension from the local number.  Optioinal, defaults to TRUE.
   *
   * @return string
   *   Local number.
   */
  public function getLocalNumber(PhoneNumber $phone_number, $strip_non_digits = FALSE, $strip_extension = TRUE);

  /**
   * Gets the country phone number prefix given a country code.
   *
   * @param string $country
   *   Country code (Eg. IL).
   *
   * @return int
   *   Country phone number prefix (Eg. 972).
   */
  public function getCountryCode($country);

  /**
   * Get all supported countries.
   *
   * @param array|null $filter
   *   Limit options to the ones in the filter.
   *   (Eg. ['IL' => 'IL', 'US' => 'US'].
   * @param bool $show_country_names
   *   Whether to show full country name instead of country codes.
   *
   * @return array
   *   Array of options, with country code as keys. (Eg. ['IL' => 'IL (+972)'])
   */
  public function getCountryOptions(array $filter = NULL, $show_country_names = FALSE);

  /**
   * Get all supported phone number types.
   *
   * @return array
   *   Array of supported phone number types, keyed by
   *   \libphonenumber\PhoneNumberType constants.
   */
  public function getTypeOptions();

}
