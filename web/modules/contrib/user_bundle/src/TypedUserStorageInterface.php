<?php

namespace Drupal\user_bundle;

use Drupal\user\UserStorageInterface;

/**
 * Defines an interface for typed user entity storage classes.
 */
interface TypedUserStorageInterface extends UserStorageInterface {

  /**
   * Updates all users of one account type to be of another type.
   *
   * @param string $old_type
   *   The current account type of the users.
   * @param string $new_type
   *   The new account type of the users.
   *
   * @return int
   *   The number of users whose account type field was modified.
   */
  public function updateType($old_type, $new_type);

}
