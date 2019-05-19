<?php

declare(strict_types=1);

namespace Drupal\testtools;

use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * Default permission matrix implementation.
 */
final class PermissionMatrix implements IteratorAggregate, PermissionMatrixInterface {

  /**
   * List of accounts.
   *
   * @var \Drupal\testtools\AccountListInterface
   */
  protected $accounts;

  /**
   * List of rows.
   *
   * @var \Drupal\testtools\Row[]
   */
  protected $rows = [];

  /**
   * PermissionMatrix constructor.
   *
   * @param \Drupal\testtools\AccountListInterface $accounts
   *   A list of accounts for this matrix.
   */
  public function __construct(AccountListInterface $accounts) {
    $this->accounts = $accounts;
  }

  /**
   * {@inheritdoc}
   */
  public function assert(callable $assert, bool ...$row): PermissionMatrixInterface {
    if (($rowcount = count($row)) !== ($accountcount = count($this->accounts->getAliases()))) {
      throw new LogicException("Expected {$accountcount} items, got {$rowcount}.", $accountcount);
    }

    $this->rows[] = new Row($assert, ...$row);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function assertAllowedFor(callable $assert, string ...$aliases): PermissionMatrixInterface {
    return $this->assert($assert, ...$this->generateRow(TRUE, ...$aliases));
  }

  /**
   * {@inheritdoc}
   */
  public function assertForbiddenFor(callable $assert, string ...$aliases): PermissionMatrixInterface {
    return $this->assert($assert, ...$this->generateRow(FALSE, ...$aliases));
  }

  /**
   * Generates a row into the permission matrix.
   *
   * @param bool $valueOnMatch
   *   Expected value on match with an account in $aliases.
   * @param string ...$aliases
   *   List of aliases.
   *
   * @return array
   *   Matrix row.
   */
  private function generateRow(bool $valueOnMatch, string ...$aliases): array {
    return array_map(function ($alias) use ($valueOnMatch, $aliases): bool {
      return $valueOnMatch === in_array($alias, $aliases);
    }, $this->accounts->getAliases());
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): Traversable {
    foreach ($this->rows as $row) {
      foreach ($this->accounts->getAliases() as $i => $alias) {
        $assert = $row->getAssert();
        $account = $this->accounts->getAccount($alias);
        yield new PermissionCheckResult($assert($account), $row->getRow()[$i], $account, $assert);
      }
    }
  }

}
