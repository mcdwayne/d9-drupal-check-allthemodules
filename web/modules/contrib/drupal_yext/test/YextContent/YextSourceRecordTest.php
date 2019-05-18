<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\YextSourceRecord;
use PHPUnit\Framework\TestCase;

/**
 * Test YextSourceRecord.
 *
 * @group myproject
 */
class YextSourceRecordTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextSourceRecord::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
