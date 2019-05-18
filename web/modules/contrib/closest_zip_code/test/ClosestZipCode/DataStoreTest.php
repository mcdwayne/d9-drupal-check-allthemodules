<?php

namespace Drupal\closest_zip_code\Tests;

use Drupal\closest_zip_code\ClosestZipCode\DataStore;
use PHPUnit\Framework\TestCase;

/**
 * Test DataStore.
 *
 * @group myproject
 */
class DataStoreTest extends TestCase {

  /**
   * Test for line().
   *
   * @param string $message
   *   The test message.
   * @param string $input
   *   The input zip.
   * @param string $exception
   *   The exception class expected or an empty string if no exception is
   *   expected.
   * @param array $expected
   *   The expected result; ignored if an exception is expected.
   *
   * @cover ::line
   * @dataProvider providerLine
   */
  public function testLine(string $message, string $input, string $exception, array $expected) {
    $object = $this->getMockBuilder(DataStore::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'lines',
        't',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->method('lines')
      ->willReturn([
        '00500' => ['a'],
        '00600' => ['b'],
        '09999' => ['c'],
        '20000' => ['d'],
        '99500' => ['e'],
        '99999' => ['f'],
      ]);

    $object->method('t')
      ->will($this->returnCallback(function ($s, $a = []) {
        return serialize([$s, $a]);
      }));

    if ($exception) {
      $this->expectException($exception);
    }
    $output = $object->line($input);

    if ($output != $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testLine().
   */
  public function providerLine() {
    return [
      [
        'message' => 'Existing',
        'input' => '00600',
        'exception' => '',
        'expected' => ['b'],
      ],
      [
        'message' => 'Does not exist',
        'input' => '30000',
        'exception' => '\Exception',
        'expected' => ['ignore'],
      ],
      [
        'message' => 'Out of bounds, lower',
        'input' => '00499',
        'exception' => '\Exception',
        'expected' => ['ignore'],
      ],
      [
        'message' => 'Has leading zeros',
        'input' => '00507',
        'exception' => '',
        'expected' => ['a'],
      ],
      [
        'message' => 'No leading zeros',
        'input' => '20049',
        'exception' => '',
        'expected' => ['d'],
      ],
      [
        'message' => 'No zips close enough',
        'input' => '20051',
        'exception' => '\Exception',
        'expected' => ['ignore'],
      ],
      [
        'message' => 'Out of bounds, higher',
        'input' => '100000',
        'exception' => '\Exception',
        'expected' => ['ignore'],
      ],
      [
        'message' => 'Fuzziness',
        'input' => '20001',
        'exception' => '',
        'expected' => ['d'],
      ],
      [
        'message' => 'Not numeric',
        'input' => 'this is not a number',
        'exception' => '\Exception',
        'expected' => ['ignore'],
      ],
    ];
  }

}
