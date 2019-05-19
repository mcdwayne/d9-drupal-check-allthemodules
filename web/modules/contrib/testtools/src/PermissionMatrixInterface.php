<?php

namespace Drupal\testtools;

use Traversable;

/**
 * Verifies a permission matrix.
 */
interface PermissionMatrixInterface extends Traversable {

  /**
   * Adds a row to the permission matrix.
   *
   * @param callable $assert
   *   A callable that checks access for a given account. The callable gets an
   *   AccountInterface as its only parameter, and must return a boolean.
   * @param bool ...$row
   *   Whether the accounts in the account list should or should not have
   *   access. The order must be the same as the accounts are added to the
   *   account list, and the number of parameters must also be the same as
   *   the number of accounts in the account list.
   *
   * @return PermissionMatrixInterface
   *   Self.
   */
  public function assert(callable $assert, bool ...$row): PermissionMatrixInterface;

  /**
   * Adds a row to the permission matrix.
   *
   * @param callable $assert
   *   A callable that checks access for a given account. The callable gets an
   *   AccountInterface as its only parameter, and must return a boolean.
   * @param string ...$aliases
   *   A list of aliases that should have access.
   *
   * @return PermissionMatrixInterface
   *   Self.
   *
   * @see \Drupal\testtools\PermissionMatrix::assert()
   * @see \Drupal\testtools\AccountList::add()
   */
  public function assertAllowedFor(callable $assert, string ...$aliases): PermissionMatrixInterface;

  /**
   * Adds a row to the permission matrix.
   *
   * @param callable $assert
   *   A callable that checks access for a given account. The callable gets an
   *   AccountInterface as its only parameter, and must return a boolean.
   * @param string ...$aliases
   *   A list of aliases that should not have access.
   *
   * @return PermissionMatrixInterface
   *   Self.
   *
   * @see \Drupal\testtools\PermissionMatrix::assert()
   * @see \Drupal\testtools\AccountList::add()
   */
  public function assertForbiddenFor(callable $assert, string ...$aliases): PermissionMatrixInterface;

}
