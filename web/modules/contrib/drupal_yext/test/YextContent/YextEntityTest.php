<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\YextEntity;
use PHPUnit\Framework\TestCase;

/**
 * Test YextEntity.
 *
 * @group myproject
 */
class YextEntityTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextEntity::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
