<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\NodeMigrateDestinationInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test NodeMigrateDestinationInterface.
 *
 * @group myproject
 */
class NodeMigrateDestinationInterfaceTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(NodeMigrateDestinationInterface::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->assertTrue(is_object($object));
  }

}
