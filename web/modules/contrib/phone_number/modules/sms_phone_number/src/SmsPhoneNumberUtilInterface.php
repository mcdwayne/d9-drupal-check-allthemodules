<?php

namespace Drupal\sms_phone_number;

use Drupal\phone_number\PhoneNumberUtilInterface;
use libphonenumber\PhoneNumber;

/**
 * The SMS Phone Number field utility interface.
 */
interface SmsPhoneNumberUtilInterface extends PhoneNumberUtilInterface {

  const PHONE_NUMBER_UNIQUE_YES_VERIFIED = 2;

  const PHONE_NUMBER_VERIFY_NONE = 'none';
  const PHONE_NUMBER_VERIFY_OPTIONAL = 'optional';
  const PHONE_NUMBER_VERIFY_REQUIRED = 'required';

  const PHONE_NUMBER_DEFAULT_SMS_MESSAGE = "Your verification code from !site_name:\n!code";

  const VERIFY_ATTEMPTS_INTERVAL = 3600;
  const VERIFY_ATTEMPTS_COUNT = 5;
  const SMS_ATTEMPTS_INTERVAL = 60;
  const SMS_ATTEMPTS_COUNT = 1;


  /**
   * Specifies the sms_phone number was verified.
   */
  const PHONE_NUMBER_VERIFIED = 1;

  /**
   * Specifies the sms_phone number was not verified.
   */
  const PHONE_NUMBER_NOT_VERIFIED = 0;

  /**
   * Specifies the tfa was enabled.
   */
  const PHONE_NUMBER_TFA_ENABLED = 1;

  /**
   * Specifies the tfa was disabled.
   */
  const PHONE_NUMBER_TFA_DISABLED = 0;

  /**
   * Checks whether there are too many verification attempts against the number.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param string $type
   *   Flood type, 'sms' or 'verification'.
   *
   * @return bool
   *   FALSE for too many attempts on this phone number, TRUE otherwise.
   */
  public function checkFlood(PhoneNumber $phone_number, $type = 'verification');

  /**
   * Gets token generated if verification code was sent.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   *
   * @return string|null
   *   A drupal token (43 characters).
   */
  public function getToken(PhoneNumber $phone_number);

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
   * Verifies input code matches code sent to user.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param string $code
   *   Input code.
   * @param string|null $token
   *   Verification token, if verification code was not sent in this session.
   *
   * @return bool
   *   TRUE if matches
   */
  public function verifyCode(PhoneNumber $phone_number, $code, $token = NULL);

  /**
   * Send verification code to sms_phone number.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
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
  public function sendVerification(PhoneNumber $phone_number, $message, $code, array $token_data = []);

  /**
   * Is the number already verified.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   *
   * @return bool
   *   TRUE if verified.
   */
  public function isVerified(PhoneNumber $phone_number);

  /**
   * Registers code for sms_phone number and returns it's token.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param string $code
   *   Access code.
   *
   * @return string
   *   43 character token.
   */
  public function registerVerificationCode(PhoneNumber $phone_number, $code);

  /**
   * Generate hash given token and code.
   *
   * @param \libphonenumber\PhoneNumber $phone_number
   *   Phone number object.
   * @param string $token
   *   Token.
   * @param string $code
   *   Verification code.
   *
   * @return string
   *   Hash string.
   */
  public function codeHash(PhoneNumber $phone_number, $token, $code);

  /**
   * Gets sms callback for sending SMS's.
   *
   * The callback should accept $number and $message, and returns status
   * booleans.
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
   * Gets account sms_phone number if tfa was enabled for the user.
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
   *   Currently configured user field for tfa. '' if not set or tfa is not
   *   enabled.
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
