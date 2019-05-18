<?php

namespace Drupal\mask_user_data\Mask;

use Faker\Factory as Faker;
use Drupal\user\Entity\User as DUser;

/**
 * Class User. This class will handle user Masking.
 *
 * @package Drupal\mask_user_data\Mask
 */
class User {

  /**
   * Mask a given user with the information given.
   *
   * Given a user id, a map array and the user fields (optional), it will mask
   * them using the appropriate function.
   *
   * @param int $uid
   *   ID of the user.
   * @param array $map_array
   *   Mapping array containing user fields and Faker functions to run.
   * @param array $user_fields
   *   OPTIONAL User fields to use.
   *
   * @return bool
   *   Whether the operation was successful or not.
   */
  public function mask($uid, array $map_array, array $user_fields = NULL) {
    $faker = Faker::create();
    $user = DUser::load($uid);

    if (is_null($user_fields)) {
      $user_fields = $user->getFields();
    }

    try {
      foreach ($map_array as $field => $faker_function) {
        if (!empty($user_fields[$field])) {
          $value = method_exists($faker, $faker_function) ?
            $faker->{$faker_function}() :
            $faker->{$faker_function};

          $user->set($field, $value);
        }
      }

      $user->save();
      return TRUE;
    }
    catch (Exception $e) {
      // We could also use: return $e->getMessage();
      return FALSE;
    }
  }

}
