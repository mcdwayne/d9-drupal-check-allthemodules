<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the group permission calculator interface.
 */
interface GroupPermissionCalculatorInterface {

  /**
   * Calculates the anonymous group permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the anonymous group permissions.
   */
  public function calculateAnonymousPermissions();

  /**
   * Calculates the outsider group permissions for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to retrieve the outsider permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the outsider group permissions.
   */
  public function calculateOutsiderPermissions(AccountInterface $account);

  /**
   * Calculates the member group permissions for an account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to retrieve the member permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the member group permissions.
   */
  public function calculateMemberPermissions(AccountInterface $account);

  /**
   * Calculates the full group permissions for an authenticated account.
   *
   * This includes both outsider and member permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to retrieve the permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the full authenticated group permissions.
   */
  public function calculateAuthenticatedPermissions(AccountInterface $account);

  /**
   * Calculates the full group permissions for an account.
   *
   * This could either include anonymous permissions or both outsider and member
   * permissions, depending on the account's anonymous status.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to retrieve the permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the full group permissions.
   */
  public function calculatePermissions(AccountInterface $account);

}
