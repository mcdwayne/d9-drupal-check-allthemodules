<?php

namespace Drupal\libphonenumber;

use libphonenumber\PhoneNumberFormat;

/**
 * Interface for phone numbers based on the libphonenumber-for-php library.
 */
interface LibPhoneNumberInterface {

  /**
   * Returns the raw input from the user.
   *
   * @return string|null
   *   The raw input, or NULL if the number was not entered in raw format.
   */
  public function getRawInput();

  /**
   * Returns the country code.
   *
   * @return int|null
   *   The country code, or NULL if not set.
   */
  public function getCountryCode();

  /**
   * Returns the national number.
   *
   * @return string|null
   *   The national number, or NULL if not set.
   */
  public function getNationalNumber();

  /**
   * Returns the extension.
   *
   * @return string|null
   *   The extension, or NULL if not set.
   */
  public function getExtension();

  /**
   * Returns the phone number in the requested format.
   *
   * @param int $format
   *   The format to use. See PhoneNumberFormat for possible options.
   *
   * @return string
   *   The phone number in the requested format.
   *
   * @see \libphonenumber\PhoneNumberFormat
   */
  public function getFormattedNumber($format = PhoneNumberFormat::E164);

}
