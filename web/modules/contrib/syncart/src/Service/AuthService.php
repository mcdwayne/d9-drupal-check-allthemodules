<?php

namespace Drupal\syncart\Service;

use Drupal\user\Entity\User;
use Drupal\profile\Entity\Profile;
use Drupal\user\UserInterface;

/**
 * Class AuthService.
 */
class AuthService implements AuthServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getUserEmail(string $email) {
    if (empty($email)) {
      return FALSE;
    }
    $uids = \Drupal::entityQuery('user')
      ->condition('mail', $email)
      ->condition('status', 1)
      ->execute();
    if (empty($uids)) {
      return FALSE;
    }
    return User::load(
      array_shift($uids)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createUser(array $info) {
    $pos = strripos($info['email'], '@');
    $login = substr($info['email'], 0, $pos);

    $user = User::create([
      'name' => $login,
      'field_user_name' => $info['name'],
      'field_user_surname' => $info['surname'],
      'field_user_phone' => $info['phone'],
      'status' => 1,
    ]);
    $user->enforceIsNew(); /* Set this to FALSE if you want to edit (resave) an existing user object */
    $user->setEmail($info['email']);
    $user->activate();
    $user->save();
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function createProfile(UserInterface $user, array $info) {
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => $user->id(),
      'field_customer_name' => $info['name'],
      'field_customer_surname' => $info['surname'],
      'field_customer_phone' => $info['phone'],
      'field_customer_email' => $info['email'],
      'field_customer_comment' => $info['comment'],
    ]);
    $profile->setDefault(TRUE);
    $profile->save();
    return $profile;
  }

}
