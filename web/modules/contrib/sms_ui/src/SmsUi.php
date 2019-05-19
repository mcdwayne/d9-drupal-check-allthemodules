<?php

/**
 * Provides common functionality used everywhere in the sms_ui namespace.
 */

namespace Drupal\sms_ui;

use Drupal\Component\Utility\Unicode;
use Drupal\sms_ui\Utility\CountryCodes;
use Drupal\sms_ui\Utility\PhoneNumberFormatHelper;

class SmsUi {

  public static function defaultCountryCode() {
    $country_code = \Drupal::service('user.data')->get('sms_ui', \Drupal::currentUser()->id(), 'country_code');
    $country_locale = \Drupal::config('system.date')->get('country.default');
    $countries = \Drupal::service('country_manager')->getList();
    if ($country_code) {
      return $country_code;
    }
    else if (isset($countries[$country_locale])) {
      return CountryCodes::getCodeForCountry($countries[$country_locale]);
    }
    else {
      return NULL;
    }
  }

  public static function formatNumber($number, $country = NULL) {
    $country = $country ?: static::defaultCountryCode();
    return PhoneNumberFormatHelper::formatNumber($number, $country);
  }

  public static function formatNumbers($numbers, $country = NULL) {
    $country = $country ?: static::defaultCountryCode();
    return PhoneNumberFormatHelper::formatNumbers($numbers, $country);
  }

  /**
   * Gets data stored for a particular user.
   *
   * @param int $uid
   *   The user's ID.
   * @param string $name
   *   The name of the data.
   * @param mixed|null $default
   *   The default value is not set.
   *
   * @return mixed
   */
  public static function getUserData($uid, $name, $default = NULL) {
    $setting = \Drupal::service('user.data')->get('sms_ui', $uid, $name);
    return $setting ? : $default;
  }

  /**
   * Stores data for a particular user.
   *
   * @param int $uid
   *   The user's ID.
   * @param string $name
   *   The name of the data.
   * @param mixed $value
   *   The value to be set.
   */
  public static function setUserData($uid, $name, $value) {
    \Drupal::service('user.data')->set('sms_ui', $uid, $name, $value);
  }

  /**
   * Deletes data stored for a particular user.
   *
   * @param int $uid
   *   The user's ID.
   * @param string $name
   *   The name of the data.
   */
  public static function deleteUserData($uid, $name) {
    \Drupal::service('user.data')->delete('sms_ui', $uid, $name);
  }

  /**
   * Calculates the number of pages into which a long message would be divided.
   *
   * This assumes the 7-septet UDH headers would be used to concatenate longer
   * messages.
   *
   * @param string $message
   *   The message to be processed.
   *
   * @return int
   *   The number of message parts.
   *
   * @see composer-form.js
   */
  public static function calculatePages($message) {
    if (Unicode::strlen($message) < 161) {
      return 1;
    }
    else {
      return (int) (Unicode::strlen($message) / 152) + 1;
    }
  }

}
