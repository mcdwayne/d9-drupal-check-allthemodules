<?php

declare(strict_types=1);

namespace Drupal\testtools;

/**
 * Permission check row.
 *
 * @see \Drupal\testtools\PermissionMatrix
 *
 * @internal
 */
final class Row {

  /**
   * @var callable
   */
  protected $assert;

  /**
   * @var bool[]
   */
  protected $row;

  /**
   * Row constructor.
   *
   * @internal
   *
   * @param callable $action
   * @param bool ...$row
   */
  public function __construct(callable $action, bool ...$row) {
    $this->assert = $action;
    $this->row = $row;
  }

  /**
   * Returns the assert.
   *
   * @internal
   *
   * @return callable
   */
  public function getAssert(): callable {
    return $this->assert;
  }

  /**
   * Returns a row of expected results.
   *
   * @internal
   *
   * @return bool[]
   */
  public function &getRow(): array {
    return $this->row;
  }

}
