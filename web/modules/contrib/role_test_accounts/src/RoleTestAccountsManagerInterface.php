<?php

namespace Drupal\role_test_accounts;

use Drupal\Core\Config\Config;

/**
 * Class RoleTestAccountsManager.
 *
 * @package Drupal\role_test_accounts
 */
interface RoleTestAccountsManagerInterface {

  /**
   * Generate Role Test Accounts.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The updated config object.
   */
  public function generateRoleTestAccounts(Config $config = NULL);

  /**
   * Creates a user account with a given role.
   *
   * @param string $role_id
   *   The role id to assign to the new user account.
   */
  public function createTestAccount($role_id);

  /**
   * Deletes a role test account for a given role.
   *
   * @param string $role_id
   *   The role id to assign to the new user account.
   */
  public function deleteTestAccount($role_id);

  /**
   * Set the password for all Role Test Accounts.
   *
   * @param string $password
   *   The new password.
   */
  public function setRoleTestAccountsPassword($password);

  /**
   * Returns an array of all Role Test Accounts.
   *
   * @return \Drupal\user\UserInterface[]
   *   An array of user accounts.
   */
  public function getAllRoleTestAccounts();

}
