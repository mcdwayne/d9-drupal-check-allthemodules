<?php

declare(strict_types=1);

namespace Drupal\testtools\Assert;

use Drupal\Core\Session\AccountInterface;

/**
 * An abstract class for assert combining.
 */
abstract class CombinedAssert implements AssertInterface {

  /**
   * Asserts to combine.
   *
   * @var \Drupal\testtools\Assert\AssertInterface[]
   */
  private $asserts = [];

  /**
   * CombinedAssert constructor.
   *
   * @param \Drupal\testtools\Assert\AssertInterface ...$asserts
   */
  final public function __construct(AssertInterface ...$asserts) {
    if (count($asserts) === 0) {
      throw new \LogicException("No asserts given.");
    }

    $this->asserts = $asserts;
  }

  /**
   * Combines the result of two asserts.
   *
   * @param bool $r0
   *   Result of the first assert.
   * @param bool $r1
   *   Result of the second assert.
   *
   * @return bool
   *   Combined result.
   */
  abstract protected function predicate(bool $r0, bool $r1): bool;

  /**
   * {@inheritdoc}
   */
  final public function getName(): string {
    return implode(PHP_EOL, array_map(function (AssertInterface $assert): string {
      return $assert->getName();
    }, $this->asserts));
  }

  /**
   * {@inheritdoc}
   */
  final public function __invoke(AccountInterface $account): bool {
    /** @var bool $initial */
    $initial = ($this->asserts[0])($account);
    if (count($this->asserts) === 1) {
      return $initial;
    }

    return array_reduce(array_slice($this->asserts, 1, NULL, TRUE), function (bool $carry, callable $assert) use ($account): bool {
      return $this->predicate($carry, $assert($account));
    }, $initial);
  }

}
