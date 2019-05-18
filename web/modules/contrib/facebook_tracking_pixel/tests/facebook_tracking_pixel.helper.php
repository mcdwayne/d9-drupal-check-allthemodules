<?php

/**
 * @file
 * Contains tests for the Facebook Tracking Pixel module.
 */

namespace facebookTrackingPixel;

class FacebookTrackingPixelTestHelper {

  /**
   * Enable tracking for all roles.
   */
  public function enable_tracking_all_roles() {
    // Turn on tracking for roles.
    variable_set('facebook_tracking_pixel_visibility_roles', 0);
    variable_set('facebook_tracking_pixel_roles_administrator', 1);
    variable_set('facebook_tracking_pixel_roles_anonymous_user', 1);
    variable_set('facebook_tracking_pixel_roles_authenticated_user', 1);
  }

  /**
   * Enable tracking for only the testing role.
   */

  public function enable_tracking_testing_role() {
    // Inverse the visibility.
    variable_set('facebook_tracking_pixel_visibility_roles', 1);
    variable_set('facebook_tracking_pixel_roles_fb_pixel_tester', 1);
    variable_set('facebook_tracking_pixel_roles_administrator', 0);
    variable_set('facebook_tracking_pixel_roles_anonymous_user', 0);
    variable_set('facebook_tracking_pixel_roles_authenticated_user', 0);
  }

  /**
   * Remove a role from a user.
   *
   * @param $user
   *   User object or user ID.
   * @param $role_name
   *   String value of role to be removed.
   */
  public function remove_role_from_user($user, $role_name) {
    // For convenience, we'll allow user ids as well as full user objects.
    if (is_numeric($user)) {
      $user = user_load($user);
    }
    // Only remove the role if the user already has it.
    $key = array_search($role_name, $user->roles);
    if ($key == TRUE) {
      // Get the rid from the roles table.
      $roles = user_roles(TRUE);
      $rid = array_search($role_name, $roles);
      if ($rid != FALSE) {
        // Make a copy of the roles array, without the deleted one.
        $new_roles = [];
        foreach ($user->roles as $id => $name) {
          if ($id != $rid) {
            $new_roles[$id] = $name;
          }
        }
        user_save($user, ['roles' => $new_roles]);
      }
    }
  }

  /**
   * Delete all variables.
   */

  public function delete_fb_tracking_role_variables() {
    // Delete all variables via an SQL query.
    $prefix = 'facebook_tracking_pixel_roles';
    $result = db_select('variable', 'v')
      ->fields('v')
      ->condition('name', db_like($prefix) . '%', 'LIKE')
      ->execute()
      ->fetchAll();
    foreach ($result as $item) {
      db_delete('variable')
        ->condition('name', $item->name)
        ->execute();
    }
  }
}

