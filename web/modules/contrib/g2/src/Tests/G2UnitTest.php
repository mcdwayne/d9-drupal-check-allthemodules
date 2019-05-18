<?php

/**
 * @file
 * Contains G2UnitTest.
 */

namespace Drupal\g2\Tests;

use Drupal\g2\G2;
use Drupal\Tests\UnitTestCase;

/**
 * Class G2UnitTest provides unit test for G2 methods.
 *
 * @group G2
 */
class G2UnitTest extends UnitTestCase {
  /**
   * Test encodeTerminal().
   */
  public function testEncoding() {
    $this->assertEquals(G2::encodeTerminal('a'), 'a', 'G2 does not recode basic characters.');
    $this->assertEquals(G2::encodeTerminal('a/b'), 'a%2Fb', 'G2 does recode special characters.');
  }

}
