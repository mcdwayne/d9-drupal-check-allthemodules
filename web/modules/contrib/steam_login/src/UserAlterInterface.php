<?php

namespace Drupal\steam_login;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * User Alter Interface.
 */
interface UserAlterInterface extends ContainerInjectionInterface {

  /**
   * Alter User Name.
   *
   * @param string $name
   *   The user name being altered.
   * @param [type] $account
   *   The user account.
   *
   * @return string
   *   The new user name.
   */
  public function alterUserName(string $name, $account);

}
