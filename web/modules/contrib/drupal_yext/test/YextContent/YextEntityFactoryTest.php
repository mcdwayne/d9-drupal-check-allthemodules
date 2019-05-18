<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\YextEntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test YextEntityFactory.
 *
 * @group myproject
 */
class YextEntityFactoryTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextEntityFactory::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
