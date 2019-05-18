<?php

namespace Drupal\Tests\commerce_paytrail\Unit;

use Drupal\commerce_paytrail\SanitizeTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Sanitize trait unit tests.
 *
 * @group commerce_paytrail
 * @coversDefaultClass \Drupal\commerce_paytrail\SanitizeTrait
 */
class SanitizeTraitTest extends UnitTestCase {

  use SanitizeTrait;

  /**
   * @covers ::sanitize
   * @dataProvider sanitizeData
   */
  public function testSanitize($expected, $data, $regex) {
    $sanitized = $this->sanitize($data, $regex);
    $this->assertEquals($expected, $sanitized);
  }

  /**
   * Data provider for testSanitize().
   *
   * @return array
   *   The test data.
   */
  public function sanitizeData() {
    return [
      [
        'Test test 1', 'Test <test> 1', 'default',
      ],
      [
        'Test 1', 'Test 1€', 'default',
      ],
      [
        'Test test 10 OFF', 'Test ^test^ 10% OFF', 'default',
      ],
      [
        'Test message, 10 OFF, 10 OFF', 'Test: message, 10% OFF, 10$ OFF', 'strict',
      ],
    ];
  }

}
