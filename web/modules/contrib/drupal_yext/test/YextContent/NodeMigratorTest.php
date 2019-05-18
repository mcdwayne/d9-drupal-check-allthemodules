<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\NodeMigrationOnSave;
use PHPUnit\Framework\TestCase;

/**
 * Test NodeMigrationOnSave.
 *
 * @group myproject
 */
class NodeMigrationOnSaveTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(NodeMigrationOnSave::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
