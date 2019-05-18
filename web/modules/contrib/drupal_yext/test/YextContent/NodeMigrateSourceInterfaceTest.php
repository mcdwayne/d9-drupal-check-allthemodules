<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\NodeMigrateSourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test NodeMigrateSourceInterface.
 *
 * @group myproject
 */
class NodeMigrateSourceInterfaceTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(NodeMigrateSourceInterface::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->assertTrue(is_object($object));
  }

}
