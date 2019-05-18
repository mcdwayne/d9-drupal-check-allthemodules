<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\inmail\MIME\Rfc2822Address;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Rfc2822Address class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\Rfc2822Address
 *
 * @group inmail
 */
class Rfc2822AddressTest extends UnitTestCase {

  /**
   * Tests the JSON serialization of Rfc2822Address class.
   *
   * @covers \Drupal\inmail\MIME\Rfc2822Address::jsonSerialize
   *
   * @dataProvider jsonProvider
   */
  public function testJsonSerialize(Rfc2822Address $rfc2822_address, $expected_value) {
    $this->assertEquals($expected_value, json_encode($rfc2822_address));
  }

  /**
   * Data provider.
   *
   * @return array
   *   A list of Rfc2822Address objects and expected JSON output.
   */
  public function jsonProvider() {
    return [
      [
        new Rfc2822Address('', 'bob@example.com'),
        '{"name":"","address":"bob@example.com"}',
      ],
      [
        new Rfc2822Address('Bob', ''),
        '{"name":"Bob","address":""}',
      ],
      [
        new Rfc2822Address('Bob', 'bob@example.com'),
        '{"name":"Bob","address":"bob@example.com"}',
      ],
    ];
  }

}
