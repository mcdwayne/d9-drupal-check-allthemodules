<?php

namespace Drupal\Tests\akamai\Unit\Helper;

use Drupal\Tests\UnitTestCase;
use Drupal\akamai\Helper\CacheTagFormatter;

/**
 * CacheTagFormatter tests.
 *
 * @group Akamai
 */
class CacheTagFormatterTest extends UnitTestCase {

  /**
   * Tests format().
   *
   * @dataProvider tagTestCases
   */
  public function testFormat($input, $expected) {
    $helper = new CacheTagFormatter();
    $this->assertSame($helper->format($input), $expected);
  }

  /**
   * Provides tag testcase data.
   */
  public function tagTestCases() {
    return [
      [1, '1'],
      ['node:1234', 'node_1234'],
      ['node 1234', 'node_1234'],
    ];
  }

}
