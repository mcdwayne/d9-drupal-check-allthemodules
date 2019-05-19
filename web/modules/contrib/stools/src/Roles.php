<?php

namespace Drupal\stools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Roles service for checking account roles.
 *
 * Drupal accounts and users have some baked in role-checking, but does not
 * include several handy and commonly needed methods.
 */
class Roles implements ContainerInjectionInterface {

  /**
   * Constructor.
   */
  public function __construct(AccountProxyInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Check if the account has a role.
   *
   * @param string $role
   *   Role machine name.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional account interface name. Defaults to the current user.
   *
   * @return bool
   *   TRUE if the account has the requested role, FALSE if not.
   */
  public function hasRole($role, AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->account;
    }
    // Users have this baked in.
    if (method_exists($account, 'hasRole')) {
      return $account->hasRole($role);
    }

    // Accounts need to do this another way.
    return in_array($role, $account->getRoles());
  }

  /**
   * The account has at least one of the roles.
   *
   * @param array $roles
   *   An array of role machine names.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional account interface name. Defaults to the current user.
   *
   * @return bool
   *   TRUE if the account has the requested role, FALSE if not.
   */
  public function hasAnyRole(array $roles, AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->account;
    }
    foreach ($roles as $role) {
      if ($this->hasRole($account, $role)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * The account has all of the requested roles.
   *
   * @param array $roles
   *   An array of role machine names.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional account interface name. Defaults to the current user.
   *
   * @return bool
   *   TRUE if the account has the requested role, FALSE if not.
   */
  public function hasAllRoles(array $roles, AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->account;
    }
    foreach ($roles as $role) {
      if (!$this->hasRole($account, $role)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
