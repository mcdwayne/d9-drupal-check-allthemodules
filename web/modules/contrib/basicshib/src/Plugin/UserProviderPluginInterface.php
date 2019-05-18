<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:27 AM
 */

namespace Drupal\basicshib\Plugin;

use Drupal\user\UserInterface;

interface UserProviderPluginInterface {
  /**
   * Load an existing user.
   *
   * @param string $name
   *
   * @return UserInterface|null
   */
  public function loadUserByName($name);

  /**
   * Create a new user. The user will be saved by the login controller.
   *
   * @param string $name
   * @param string $mail
   *
   * @return UserInterface|null
   *   The user, or null if one cannot be created.
   */
  public function createUser($name, $mail);
}
