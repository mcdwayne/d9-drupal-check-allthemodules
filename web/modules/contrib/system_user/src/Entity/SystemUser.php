<?php

namespace Drupal\system_user\Entity;

use Drupal\system_user\Service\SystemUserManager;
use Drupal\user\Entity\User;

/**
 * Class SystemUser.
 */
class SystemUser {

  /**
   * Create a new system user based on an array of values.
   *
   * @param array $values
   *   The values to set on the user.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created system user.
   */
  public static function create(array $values = []) {
    return User::create(array_merge($values, [
      SystemUserManager::FIELD_NAME => TRUE,
    ]));
  }

}
