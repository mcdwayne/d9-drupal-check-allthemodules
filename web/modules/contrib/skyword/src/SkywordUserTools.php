<?php

namespace Drupal\skyword;

use Drupal\user\Entity\Role;

/**
 * Common User Tools that Skyword uses
 */
class SkywordUserTools {

  /**
   * Helper function to look up all roles with the Skyword Author permission
   */
  public static function getAuthorRoles() {
    $all_roles = Role::loadMultiple();
    $our_roles = [];

    /** @var \Drupal\user\Entity\role $roleObj */
    foreach ($all_roles as $role => $roleObj) {
      if ($roleObj->hasPermission('is skyword author')) {
        $our_roles[] = $roleObj->get('label');
      }
    }

    return $our_roles;
  }

  /**
   * Helper function to retrieve user first name
   *
   * Validate if the accessibility for each property and methods are present
   *
   * @param object $user
   *   The User Entity
   *
   * The first_name field does not exist
   */
//  public static function getFirstName($user) {
//    if (!isset($user->field_first_name)) {
//      return NULL;
//    }
//
//    if (!isset($user->field_first_name->value)) {
//      return NULL;
//    }
//
//    return $user->field_first_name->value;
//  }

  /**
   * Helper function to retrieve user last name
   *
   * Validate if the accessibility for each property and methods are present
   *
   * @param object $user
   *   The User Entity
   *
   * The last_name field does not exist
   */
//  public static function getLastName($user) {
//    if (!isset($user->field_last_name)) {
//      return NULL;
//    }
//
//    if (!isset($user->field_last_name->value)) {
//      return NULL;
//    }
//
//    return $user->field_last_name->value;
//  }

  /**
   * Helper function to retrieve user byline
   *
   * Validate if the accessibility for each property and methods are present
   *
   * @param object $user
   *   The User Entity
   *
   * @return string or NULL
   *   The User's byline (name) or NULL if there isn't one (there should™ always be one)
   */
  public static function getByline($user) {
    if (!isset($user->name)) {
      return NULL;
    }

    if (!isset($user->name->value)) {
      return NULL;
    }

    return $user->name->value;
  }

  /**
   * Helper function to retrieve user picture
   *
   * Validate if the accessibility for each property and methods are present
   *
   * @param object $user
   *   The User Entity
   *
   * Currently we're not using the user_picture
   */
  public static function getUserPicture($user) {
    if (!isset($user->get('user_picture')->entity)) {
      return NULL;
    }

    if (NULL == $user->get('user_picture')->entity->url()) {
      return NULL;
    }

    return $user->get('user_picture')->entity->url();
  }

}
