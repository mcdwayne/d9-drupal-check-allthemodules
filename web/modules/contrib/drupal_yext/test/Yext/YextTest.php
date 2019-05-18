<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\Yext\Yext;
use PHPUnit\Framework\TestCase;

/**
 * Test Yext.
 *
 * @group myproject
 */
class YextTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(Yext::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
