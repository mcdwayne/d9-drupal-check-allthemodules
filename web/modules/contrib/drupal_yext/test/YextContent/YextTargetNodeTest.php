<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\YextTargetNode;
use PHPUnit\Framework\TestCase;

/**
 * Test YextTargetNode.
 *
 * @group myproject
 */
class YextTargetNodeTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextTargetNode::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
