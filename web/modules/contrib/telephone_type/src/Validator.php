<?php

namespace Drupal\telephone_type;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Performs telephone_type validation.
 */
class Validator {

  /**
   * Phone Number util.
   *
   * @var \libphonenumber\PhoneNumberUtil
   */
  public $phoneUtils;

  /**
   * Validator constructor.
   */
  public function __construct() {
    $this->phoneUtils = PhoneNumberUtil::getInstance();
  }

  /**
   * Check if number is valid for given settings.
   *
   * @param string $value
   *   Phone number.
   *
   * @return bool
   *   Boolean representation of validation result.
   */
  public function isValid($value) {
    try {
      $number = $this->phoneUtils->parse($value, 'US');
    }
    catch (\Exception $e) {
      return FALSE;
    }

    // Perform validation for valid US number.
    if ($this->phoneUtils->isValidNumber($number) && $this->phoneUtils->isValidNumberForRegion($number, 'US')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get telephone number in number only format.
   *
   * @param string $value
   *   Public function getNationalNumber value.
   *
   * @return bool|string
   *   Public function getNationalNumber bool string.
   */
  public function getNationalNumber($value) {
    try {
      $number = $this->phoneUtils->parse($value, 'US');
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $this->phoneUtils->getNationalSignificantNumber($number);
  }

  /**
   * Format a telephone number, National.
   *
   * @param string $value
   *   Public function format value.
   *
   * @return bool|string
   *   Public function format bool string.
   */
  public function format($value) {
    try {
      $number = $this->phoneUtils->parse($value, 'US');
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $this->phoneUtils->format($number, PhoneNumberFormat::NATIONAL);
  }

  /**
   * Format a telephone number for use in URI.
   *
   * @param string $value
   *   Public function formatUri value.
   *
   * @return bool|string
   *   Public function formatUri bool string.
   */
  public function formatUri($value) {
    try {
      $number = $this->phoneUtils->parse($value, 'US');
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $this->phoneUtils->format($number, PhoneNumberFormat::RFC3966);
  }

}
