<?php

declare(strict_types=1);

namespace Drupal\testtools\Assert;

use Drupal\Core\Session\AccountInterface;

/**
 * Wraps a callable into an assert.
 */
final class AssertCallable extends AssertBase implements AssertInterface {

  /**
   * Internal callable.
   *
   * @var callable
   */
  protected $callable;

  /**
   * AssertCallable constructor.
   *
   * @param string $name
   *   Assert name.
   * @param callable $callable
   *   Callable to wrap.
   */
  public function __construct(string $name, callable $callable) {
    parent::__construct($name);
    $this->callable = $callable;
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(AccountInterface $account): bool {
    return ($this->callable)($account);
  }

}
