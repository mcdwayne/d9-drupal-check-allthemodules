<?php

namespace Drupal\quicker_login\Service;

/**
 * QuickerLogin service interface.
 */
interface QuickerLoginServiceInterface {

  /**
   * Login user by name.
   *
   * @param string $user_name
   *   The name of the user.
   */
  public function loginUserName($user_name);

}
