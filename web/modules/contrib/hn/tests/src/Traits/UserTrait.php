<?php

namespace Drupal\Tests\hn\Traits;

use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Base for user operations.
 */
trait UserTrait {

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser(array $values = [], array $permissions = []) {
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = Role::create([
        'id' => strtolower($this->randomMachineName(8)),
        'label' => $this->randomMachineName(8),
      ]);
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = User::create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

}
