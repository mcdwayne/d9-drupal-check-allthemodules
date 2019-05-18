<?php

namespace Drupal\dev_roles_users\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Development Roles Users.
 */
class DevRolesUsersController extends ControllerBase {

  /**
   * Service for DevRolesUsers.
   *
   * @var \Drupal\dev_roles_users\DevRolesUsersNameFormatter
   */
  protected $devRolesUsersNameFormatter;

  /**
   * Gets user information for each role.
   */
  public static function getUsersInfo() {

    $users = array();
    $roles = user_roles(TRUE);

    // Remove "authenticated user" form the roles list.
    unset($roles[DRUPAL_AUTHENTICATED_RID]);

    foreach ($roles as $role) {
      $users[] = self::getUserFromRole($role);
    }

    return $users;
  }

  /**
   * Generates a user array from a role.
   */
  public static function getUserFromRole($role) {
    global $base_url;

    $dev_roles_users_name_formatter = \Drupal::service('dev_roles_users.dev_roles_users_name_formatter');

    $original_id = $role->getOriginalId();

    $label = $role->get('label');

    $username = $dev_roles_users_name_formatter->getCleanUsername($label);

    // Clean $base_url to be used as email domain.
    $mail_domain = preg_replace('#http(s)?://(www.)?#', '', $base_url);

    $mail = $username . '@' . $mail_domain;

    $user = [
      'name' => $username,
      'pass' => $username,
      'mail' => $mail,
      'status' => 1,
      'roles' => [
        $original_id,
      ],
    ];

    return $user;
  }

  /**
   * Insert User By Info.
   */
  public static function insertUserByInfo($user_info) {

    if (empty($user_info['pass']) || empty($user_info['mail']) || empty($user_info['name']) || empty($user_info['roles'])) {
      return FALSE;
    }

    $user_name = $user_info['name'];
    $password = $user_info['pass'];
    $email = $user_info['mail'];
    $roles = $user_info['roles'];

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $user = User::create();

    $user->setPassword($password);
    $user->enforceIsNew();
    $user->setEmail($email);
    $user->setUsername($user_name);
    $user->set('langcode', $language);
    $user->set('preferred_langcode', $language);
    $user->set('preferred_admin_langcode', $language);

    foreach ($roles as $rid) {
      $user->addRole($rid);
    }

    $user->activate();

    // Save user account.
    $user->save();
  }

}
