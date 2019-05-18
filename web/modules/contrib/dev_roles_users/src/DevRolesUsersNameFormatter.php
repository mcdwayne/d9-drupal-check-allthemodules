<?php

namespace Drupal\dev_roles_users;

/**
 * Service for DevRolesUsers.
 */
class DevRolesUsersNameFormatter {

  /**
   * Gets clean username from role name.
   */
  public function getCleanUsername($role) {
    return strtolower(preg_replace('/[\s]+/', '.', $role));
  }

}
