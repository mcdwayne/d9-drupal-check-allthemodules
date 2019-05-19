<?php

declare(strict_types=1);

namespace Drupal\testtools\Assert;

use Drupal\Core\Session\AccountInterface;

/**
 * Extends a callable with a name.
 */
interface AssertInterface {

  /**
   * Returns the assert's name.
   *
   * @return string
   *   Assert name.
   */
  public function getName(): string;

  /**
   * Runs the access check.
   *
   * @see \Drupal\testtools\PermissionMatrix::assert()
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to assert.
   *
   * @return bool
   *   Whether the account has access or not.
   */
  public function __invoke(AccountInterface $account): bool;

}
