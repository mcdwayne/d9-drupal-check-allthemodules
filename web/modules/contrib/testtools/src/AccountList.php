<?php

declare(strict_types=1);

namespace Drupal\testtools;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use LogicException;

/**
 * Default account list implementation.
 */
final class AccountList implements AccountListInterface {

  /**
   * Map of alias => account.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $accounts = [];

  /**
   * {@inheritdoc}
   */
  public function add(string $alias, AccountInterface $account): AccountListInterface {
    $this->accounts[$alias] = $account;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliases(): array {
    return array_keys($this->accounts);
  }

  /**
   * Adds the anonymous user.
   *
   * @return \Drupal\testtools\AccountListInterface
   *   Self.
   */
  public function addAnonymous(): AccountListInterface {
    return $this->add('anon', new AnonymousUserSession());
  }

  /**
   * Adds user/1.
   *
   * @param \Drupal\Core\Session\AccountInterface $rootUser
   *   Root user. This cannot be loaded with User::load(), because then it
   *   won't be able to log in properly.
   *
   * @return \Drupal\testtools\AccountListInterface
   *   Self.
   */
  public function addRoot(AccountInterface $rootUser): AccountListInterface {
    if ($rootUser->id() !== 1) {
      throw new LogicException("The account must be the root user.");
    }

    return $this->add('root', $rootUser);
  }

  /**
   * Adds multiple accounts. The aliases are the account names.
   *
   * @param \Drupal\Core\Session\AccountInterface ...$accounts
   *   Accounts to add.
   *
   * @return \Drupal\testtools\AccountListInterface
   *   Self.
   */
  public function addMultiple(AccountInterface ...$accounts): AccountListInterface {
    return array_reduce($accounts, function (self $self, AccountInterface $account): self {
      return $self->add($account->getAccountName(), $account);
    }, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(string $alias): ?AccountInterface {
    return $this->accounts[$alias] ?? NULL;
  }

}
