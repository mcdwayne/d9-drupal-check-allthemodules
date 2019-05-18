<?php

namespace Drupal\onelogin_integration;

/**
 * Interface UserServiceInterface for the OneLogin Integration module.
 *
 * This interface defines that, when implemented, the function createUser should
 * be used. This is basically the only and most important function of the
 * interface.
 *
 * @package Drupal\onelogin_integration
 */
interface UserServiceInterface {

  /**
   * Creates a user.
   *
   * @param string $username
   *   The username for the new user.
   * @param string $email
   *   The email for the new user.
   */
  public function createUser($username, $email);

}
