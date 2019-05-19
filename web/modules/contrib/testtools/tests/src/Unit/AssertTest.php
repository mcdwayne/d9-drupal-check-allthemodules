<?php

declare(strict_types=1);

namespace Drupal\Testing\testtools\Unit;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\testtools\Assert\AssertCallable;
use Drupal\testtools\Assert\AssertInterface;
use Drupal\testtools\Assert\CombinedAssertAnd;
use Drupal\testtools\Assert\CombinedAssertOr;

/**
 * Tests assert combinations.
 */
class AssertTest extends UnitTestCase {

  /**
   * Tests assert combinations.
   *
   * @dataProvider assertCombinationProvider
   *
   * @param string $combineClass
   *   Name of the assert combining class.
   * @param bool $result
   *   Expected result.
   * @param callable[] $asserts
   *   List of the asserts to combine.
   */
  public function testAssertCombination(string $combineClass, bool $result, callable ...$asserts): void {
    /** @var \Drupal\testtools\Assert\CombinedAssert $combine */
    $combine = new $combineClass(...array_map(function (callable $assert): AssertInterface {
      return new AssertCallable('', $assert);
    }, $asserts));

    static::assertEquals($result, $combine(new class() implements AccountInterface {
      public function id() {}
      public function getRoles($exclude_locked_roles = FALSE) {}
      public function hasPermission($permission) {}
      public function isAuthenticated() {}
      public function isAnonymous() {}
      public function getPreferredLangcode($fallback_to_default = TRUE) {}
      public function getPreferredAdminLangcode($fallback_to_default = TRUE) {}
      public function getUsername() {}
      public function getAccountName() {}
      public function getDisplayName() {}
      public function getEmail() {}
      public function getTimeZone() {}
      public function getLastAccessedTime() {}
    }));
  }

  /**
   * Provides combinations for testAssertCombination().
   *
   * @return array
   */
  public function assertCombinationProvider(): array {
    $t = function (): bool { return TRUE; };
    $f = function (): bool { return FALSE; };
    $and = CombinedAssertAnd::class;
    $or = CombinedAssertOr::class;

    return [
      [$and, TRUE, $t, $t, $t],
      [$and, FALSE, $t, $f, $t],
      [$and, TRUE, $t],
      [$and, FALSE, $f],
      [$or, TRUE, $f, $t, $f],
      [$or, FALSE, $f, $f, $f],
      [$or, TRUE, $t],
      [$or, FALSE, $f],
    ];
  }

  /**
   * CombinedAssert creation must throw an exception when no arguments are given.
   *
   * @dataProvider assertCombinationEmptyProvider
   *
   * @param string $classname
   *   A class that extends CombinedAssert.
   */
  public function testEmptyCombines(string $classname): void {
    $this->expectException(\LogicException::class);
    new $classname();
  }

  /**
   * Provides class names to testEmptyCombines.
   *
   * @return array
   */
  public function assertCombinationEmptyProvider(): array {
    return [
      [CombinedAssertAnd::class],
      [CombinedAssertOr::class],
    ];
  }

}
