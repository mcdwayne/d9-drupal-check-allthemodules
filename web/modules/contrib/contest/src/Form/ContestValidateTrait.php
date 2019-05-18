<?php

namespace Drupal\contest\Form;

use Drupal\contest\ContestUser;
use Drupal\contest\ContestStorage;

/**
 * Common validation methods.
 */
trait ContestValidateTrait {

  /**
   * Determine if the user has a complete profile.
   *
   * @param int $uid
   *   The user's ID.
   * @param string $role
   *   The user's role.
   *
   * @return bool
   *   True of the profile is complete, otherwise false.
   */
  public static function completeProfile($uid, $role = '') {
    $usr = new ContestUser($uid);

    return $usr->completeProfile($role);
  }

  /**
   * Field validation.
   *
   * @param string $type
   *   The type of validation tests to run on the field.
   * @param int|string $value
   *   The value fo the field we're validating.
   *
   * @return bool
   *   True if the field is valid.
   */
  public static function validField($type, $value) {
    if (empty($type) || !isset($value)) {
      return FALSE;
    }
    switch ($type) {
      case 'address':
        return (bool) preg_match('/[a-zA-Z]{2,' . ContestStorage::ADDR_MAX . '}/', $value);

      case 'city':
        return (bool) preg_match('/[a-zA-Z\s-.]{2,' . ContestStorage::CITY_MAX . '}/', $value);

      case 'complete_profile':
        return self::completeProfile($value, 'host');

      case 'dob':
        return (bool) is_numeric($value);

      case 'email':
        return (bool) (strlen($value) <= Email::EMAIL_MAX_LENGTH && \Drupal::service('email.validator')->isValid($value));

      case 'email_dupe':
        return ContestStorage::usrMailExists($value);

      case 'filesystem':
        return (bool) (preg_match('/^\w+$/', trim($value)) && strlen(trim($value)) <= ContestStorage::STRING_MAX);

      case 'int':
        return (bool) (is_numeric($value) && intval($value) > 0 && intval($value) <= ContestStorage::INT_MAX);

      case 'name':
        return (bool) preg_match('/[a-zA-Z]{1,' . ContestStorage::NAME_MAX . '}/', $value);

      case 'phone':
        return (bool) (strlen(preg_replace('/\D+/', '', $value)) >= 10 && strlen($value) < ContestStorage::PHONE_MAX);

      case 'state':
        $states = ContestHelper::getStates(\Drupal::config('system.date')->get('country.default'));
        return !empty($states[$value]);

      case 'string':
        return (bool) (preg_match('/[a-zA-z]+/', $value) && strlen(trim($value)) <= ContestStorage::STRING_MAX);

      case 'uid':
        return self::completeProfile($value);

      case 'username':
        return (bool) preg_match('/\w{2,' . USERNAME_MAX_LENGTH . '}/', $value);

      case 'username_dupe':
        return ContestStorage::usrNameExists($value);

      case 'zip':
        return (bool) (strlen(preg_replace('/\D+/', '', $value)) == ContestStorage::ZIP_MAX);
    }
    return FALSE;
  }

}
