<?php

namespace Drupal\mobile_number;

use libphonenumber\PhoneNumber;

/**
 * Provides an interface for mobile number utility.
 */
interface MobileNumberUtilInterface {

  const MOBILE_NUMBER_UNIQUE_NO = 0;
  const MOBILE_NUMBER_UNIQUE_YES = 1;
  const MOBILE_NUMBER_UNIQUE_YES_VERIFIED = 2;

  const MOBILE_NUMBER_VERIFY_NONE = 'none';
  const MOBILE_NUMBER_VERIFY_OPTIONAL = 'optional';
  const MOBILE_NUMBER_VERIFY_REQUIRED = 'required';

  const MOBILE_NUMBER_DEFAULT_SMS_MESSAGE = "Your verification code from !site_name:\n!code";

  const VERIFY_ATTEMPTS_INTERVAL = 3600;
  const VERIFY_ATTEMPTS_COUNT = 5;
  const SMS_ATTEMPTS_INTERVAL = 60;
  const SMS_ATTEMPTS_COUNT = 1;


  /**
   * Specifies the mobile number was verified.
   */
  const MOBILE_NUMBER_VERIFIED = 1;

  /**
   * Specifies the mobile number was not verified.
   */
  const MOBILE_NUMBER_NOT_VERIFIED = 0;

  /**
   * Specifies the tfa was enabled.
   */
  const MOBILE_NUMBER_TFA_ENABLED = 1;

  /**
   * Specifies the tfa was disabled.
   */
  const MOBILE_NUMBER_TFA_DISABLED = 0;

  /**
   * Get libphonenumber Util instance.
   *
   * @return \libphonenumber\PhoneNumberUtil
   *   Libphonenumber utility instance.
   */
  public function libUtil();

  /**
   * Get mobile number object.
   *
   * @param string $number
   *   Number.
   * @param null|string $country
   *   Country.
   * @param array $types
   *   Mobile number types to verify as defined in
   *   \libphonenumber\PhoneNumberType.
   *
   * @return \libphonenumber\PhoneNumber|null
   *   Phone Number object if successful.
   */
  public function getMobileNumber($number, $country = NULL, $types = [
    1 => 1,
    2 => 2,
  ]);

  /**
   * Test mobile number validity.
   *
   * @param string $number
   *   Number.
   * @param null|string $country
   *   Country.
   * @param array $types
   *   Mobile number types to verify as defined in
   *   \libphonenumber\PhoneNumberType.
   *
   * @throws \Drupal\mobile_number\Exception\MobileNumberException
   *   Thrown if mobile number is not valid.
   *
   * @return \libphonenumber\PhoneNumber
   *   Libphonenumber Phone number object.
   */
  public function testMobileNumber($number, $country = NULL, $types = [
    1 => 1,
    2 => 2,
  ]);

  /**
   * Get international number.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   *
   * @return string
   *   E.164 formatted number.
   */
  public function getCallableNumber(PhoneNumber $mobile_number);

  /**
   * Get country code.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   *
   * @return string
   *   Country code.
   */
  public function getCountry(PhoneNumber $mobile_number);

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
   * Get national number.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   *
   * @return string
   *   National number.
   */
  public function getLocalNumber(PhoneNumber $mobile_number);

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
   * Checks whether there were too many verifications attempted with the current number.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   * @param string $type
   *   Flood type, 'sms' or 'verification'.
   *
   * @return bool
   *   FALSE for too many attempts on this mobile number, TRUE otherwise.
   */
  public function checkFlood(PhoneNumber $mobile_number, $type = 'verification');

  /**
   * Gets token generated if verification code was sent.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   *
   * @return string|null
   *   A drupal token (43 characters).
   */
  public function getToken(PhoneNumber $mobile_number);

  /**
   * Generates a random numeric string.
   *
   * @param int $length
   *   Number of digits.
   *
   * @return string
   *   Code in length of $length.
   */
  public function generateVerificationCode($length = 4);

  /**
   * Get all supported countries.
   *
   * @param array $filter
   *   Limit options to the ones in the filter. (Eg. ['IL' => 'IL', 'US' => 'US'].
   * @param bool $show_country_names
   *   Whether to show full country name instead of country codes.
   *
   * @return array
   *   Array of options, with country code as keys. (Eg. ['IL' => 'IL (+972)'])
   */
  public function getCountryOptions($filter = [], $show_country_names = FALSE);

  /**
   * Verifies input code matches code sent to user.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   * @param string $code
   *   Input code.
   * @param string|null $token
   *   Verification token, if verification code was not sent in this session.
   *
   * @return bool
   *   TRUE if matches
   */
  public function verifyCode(PhoneNumber $mobile_number, $code, $token = NULL);

  /**
   * Send verification code to mobile number.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   * @param string $message
   *   Drupal translatable string.
   * @param string $code
   *   Code to send.
   * @param array $token_data
   *   Token variables to be used with token_replace().
   *
   * @return bool
   *   Success flag.
   */
  public function sendVerification(PhoneNumber $mobile_number, $message, $code, $token_data = []);

  /**
   * Is the number already verified.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   *
   * @return bool
   *   TRUE if verified.
   */
  public function isVerified(PhoneNumber $mobile_number);

  /**
   * Registers code for mobile number and returns it's token.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   * @param string $code
   *   Access code.
   *
   * @return string
   *   43 character token.
   */
  public function registerVerificationCode(PhoneNumber $mobile_number, $code);

  /**
   * Generate hash given token and code.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Phone number object.
   * @param string $token
   *   Token.
   * @param string $code
   *   Verification code.
   *
   * @return string
   *   Hash string.
   */
  public function codeHash(PhoneNumber $mobile_number, $token, $code);

  /**
   * Gets sms callback for sending SMS's. The callback should accept $number and $message, and returns status booleans.
   *
   * @return callable
   *   SMS callback.
   */
  public function smsCallback();

  /**
   * Checks if sms sending is enabled.
   *
   * @return bool
   *   True or false.
   */
  public function isSmsEnabled();

  /**
   * Sends an sms, based on callback provided by smsCallback().
   *
   * @param string $number
   *   A callable number in international format.
   * @param string $message
   *   String message, after translation.
   *
   * @return bool
   *   SMS callback result, TRUE = success, FALSE otherwise.
   */
  public function sendSms($number, $message);

  /**
   * Gets account mobile number if tfa was enabled for the user.
   *
   * @param int $uid
   *   User id.
   *
   * @return string
   *   International number
   */
  public function tfaAccountNumber($uid);

  /**
   * Gets the tfa field configuration.
   *
   * @return string
   *   Currently configured user field for tfa. '' if not set or tfa is not enabled.
   */
  public function getTfaField();

  /**
   * Sets the tfa field configuration.
   *
   * @param string $field_name
   *   User field name.
   */
  public function setTfaField($field_name);

  /**
   * Checks if tfa is enabled.
   *
   * @return bool
   *   True or false.
   */
  public function isTfaEnabled();

}
