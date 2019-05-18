<?php

namespace Drupal\exception_mailer\Utility;

use Drupal\user\Entity\User;

/**
 * Helper class to grab user data.
 */
class UserRepository {

  /**
   * Get email addresses for a targeted user role.
   *
   * @param string $role
   *   The role id of the targeted user role.
   *
   * @return array
   *   Returns an array of email addresses.
   */
  public static function getUserEmails($role) {
    $user_emails = [];
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', $role)
      ->execute();
    $users = User::loadMultiple($ids);
    foreach ($users as $user) {
      array_push($user_emails, $user->getEmail());
    }
    return $user_emails;
  }

}
