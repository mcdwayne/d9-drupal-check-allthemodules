<?php

namespace Drupal\decoupled_auth\Tests;

use Drupal\decoupled_auth\Entity\DecoupledAuthUser;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides methods to create additional test users for decoupled auth tests.
 *
 * This trait is meant to be used only by test classes extending
 * \Drupal\simpletest\TestBase or Drupal\KernelTests\KernelTestBase.
 */
trait DecoupledAuthUserCreationTrait {

  /**
   * Create a decoupled user with the given email and save it.
   *
   * @param string $email_prefix
   *   This is suffixed with '@example.com' for the mail and, if not decoupled,
   *   is used for the name of the user. If not given, a random name will be
   *   generated.
   *
   * @return \Drupal\decoupled_auth\Entity\DecoupledAuthUser
   *   The created user.
   */
  protected function createDecoupledUser($email_prefix = NULL) {
    $user = $this->createUnsavedUser(TRUE, $email_prefix);
    $user->save();
    $this->assertTrue($user, SafeMarkup::format('Decoupled user successfully created with the email %email.', ['%mail' => $user->getEmail()]));
    return $user;
  }

  /**
   * Create a user  with the given email without saving it.
   *
   * @param bool $decoupled
   *   Whether the created user should be decoupled. If coupled, 'name' will be
   *   set to $email_prefix.
   * @param string $email_prefix
   *   This is suffixed with '@example.com' for the mail and, if not decoupled,
   *   is used for the name of the user. If not given, a random name will be
   *   generated.
   * @param array $values
   *   An array of additional values to set. status will be set to 1 if not
   *   explicitly given.
   *
   * @return \Drupal\decoupled_auth\Entity\DecoupledAuthUser
   *   The created unsaved user.
   */
  protected function createUnsavedUser($decoupled, $email_prefix = NULL, array $values = []) {
    // Generate a random name if we don't have one.
    if (!$email_prefix) {
      $email_prefix = $this->randomMachineName();
    }

    // Create and save our user.
    $values += [
      'status' => 1,
    ];
    $values['mail'] = $email_prefix . '@example.com';
    $values['name'] = $decoupled ? NULL : $email_prefix;
    /** @var \Drupal\decoupled_auth\Entity\DecoupledAuthUser $user */
    $user = DecoupledAuthUser::create($values);

    // Set the given name as a property so it can be accessed when the user is
    // decoupled.
    $user->email_prefix = $email_prefix;

    return $user;
  }

}
