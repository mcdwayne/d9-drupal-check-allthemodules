<?php

declare(strict_types=1);

namespace Drupal\testtools;

use Drupal\Core\Session\AccountInterface;
use Drupal\testtools\Assert\AssertInterface;

/**
 * A result of a permission check.
 *
 * @internal
 */
final class PermissionCheckResult {

  /**
   * @var bool
   */
  protected $actual;

  /**
   * @var bool
   */
  protected $expected;

  /**
   * Account of the check.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Assert of the check.
   *
   * @var callable
   */
  protected $assert;

  /**
   * PermissionCheckResult constructor.
   *
   * @param bool $actual
   * @param bool $expected
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   * @param callable $assert
   *   Assert.
   */
  public function __construct(bool $actual, bool $expected, AccountInterface $account, callable $assert) {
    $this->actual = $actual;
    $this->expected = $expected;
    $this->account = $account;
    $this->assert = $assert;
  }

  /**
   * Returns the result.
   *
   * @return bool
   */
  public function getResult(): bool {
    return $this->actual === $this->expected;
  }

  /**
   * The actual result of the assertion.
   *
   * @return bool
   */
  public function getActual(): bool {
    return $this->actual;
  }

  /**
   * The expected result of the assertion.
   *
   * @return bool
   */
  public function getExpected(): bool {
    return $this->expected;
  }

  /**
   * Returns the account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function getAccount(): AccountInterface {
    return $this->account;
  }

  /**
   * Returns the assert.
   *
   * @return callable
   */
  public function getAssert(): callable {
    return $this->assert;
  }

  /**
   * Returns the name of the assert if it has one.
   *
   * @return null|string
   */
  public function getName(): ?string {
    return $this->assert instanceof AssertInterface ? $this->assert->getName() : NULL;
  }

}
