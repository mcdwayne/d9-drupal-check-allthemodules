<?php

/**
 * @file
 * Describes hooks provided by the Metatag User Role module.
 */

/**
 * Alter the roles used to extract meta tags.
 *
 * @param \Drupal\user\RoleInterface[] $roles
 *   An associative array with the role id as the key and the role object as
 *   value.
 * @param \Drupal\user\UserInterface $user
 *   The user entity to extract meta tags from.
 */
function hook_metatag_user_role_roles_alter(array &$roles, \Drupal\user\UserInterface $user) {
  // Move role with id "my_role" to the beginning of the list.
  // This gives this role the highest priority when extracting meta tags.
  if ($user->hasRole('my_role')) {
    $temp = ['my_role' => $roles['my_role']];
    unset($roles['my_role']);
    $roles = $temp + $roles;
  }
}
