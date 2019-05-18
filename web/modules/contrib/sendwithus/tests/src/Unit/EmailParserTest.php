<?php

namespace Drupal\Tests\sendwithus\Unit;

use Drupal\sendwithus\EmailParserTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Email related unit tests.
 *
 * @group sendwithus
 * @coversDefaultClass \Drupal\sendwithus\EmailParserTrait
 */
class EmailParserTest extends UnitTestCase {

  /**
   * @covers ::parseAddresses
   * @dataProvider dataProvider
   */
  public function testParseAddresses(string $addresses, array $excepted) {
    $mock = $this->getMockForTrait(EmailParserTrait::class);
    $parsed = $mock->parseAddresses($addresses);

    $this->assertEquals($excepted, $parsed);
  }

  /**
   * The data provider for ::testDefault.
   *
   * @return array
   *   The data.
   */
  public function dataProvider() {
    return [
      [
        'test@example.com',
        [
          ['address' => 'test@example.com'],
        ],
      ],
      [
        'test@example.com, test2@example.com',
        [
          ['address' => 'test@example.com'],
          ['address' => 'test2@example.com'],
        ],
      ],
      [
        'FirstName LastName <test@example.com>, test2@example.com',
        [
          ['address' => 'test@example.com', 'name' => 'FirstName LastName'],
          ['address' => 'test2@example.com'],
        ],
      ],
      [
        'FirstName LastName <test@example.com>, TEstname testname <test2@example.com>',
        [
          ['address' => 'test@example.com', 'name' => 'FirstName LastName'],
          ['address' => 'test2@example.com', 'name' => 'TEstname testname'],
        ],
      ],
    ];
  }

}
