<?php

namespace Drupal\testtools;

use Drupal\Core\Session\AccountInterface;

/**
 * A container class to hold a list of accounts.
 */
interface AccountListInterface {

  /**
   * Adds an account to the account list.
   *
   * @param string $alias
   *   Alias for the account.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   *
   * @return \Drupal\testtools\AccountListInterface
   *   Self.
   */
  public function add(string $alias, AccountInterface $account): AccountListInterface;

  /**
   * Returns the list of the aliases.
   *
   * @return string[]
   *   Alias list.
   */
  public function getAliases(): array;

  /**
   * Returns an account.
   *
   * @param string $alias
   *   Account alias.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   Found account or null.
   */
  public function getAccount(string $alias): ?AccountInterface;

}
