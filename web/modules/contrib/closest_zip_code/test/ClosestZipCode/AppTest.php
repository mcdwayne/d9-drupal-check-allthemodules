<?php

namespace Drupal\closest_zip_code\Tests;

use Drupal\closest_zip_code\ClosestZipCode\App;
use Drupal\closest_zip_code\ClosestZipCode\DataStore;
use Drupal\closest_zip_code\ClosestZipCode\Location;
use PHPUnit\Framework\TestCase;

/**
 * Test App.
 *
 * @group myproject
 */
class AppTest extends TestCase {

  public function callbackLocation($zip) {
    return new class($zip) extends Location {
      public function __construct($zip) {
        $this->zip = $zip;
      }
      public function km(Location $start) : float {
        return round(111 * $this->zip, 2);
      }
      public function miles(Location $start) : float {
        return round(999 * $this->zip, 2);
      }
      public function lat() : float {
        return round(111.111 * $this->zip, 2);
      }
      public function lon() : float {
        return round(999.999 * $this->zip, 2);
      }
    };
  }

  /**
   * Test for closestZipCode().
   *
   * @param string $message
   *   The test message.
   * @param string $my_zip
   *   An initial zip code.
   * @param array $all_zips
   *   All zip codes to check.
   * @param array $expected
   *   The expected result.
   *
   * @cover ::closestZipCode
   * @dataProvider providerClosestZipCode
   */
  public function testClosestZipCode(string $message, string $my_zip, array $all_zips, array $expected) {
    $object = $this->getMockBuilder(App::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'dataStore',
        'location',
        'microtime',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->method('dataStore')
      ->willReturn(new class extends DataStore {
        public function preload() {}
      });

    $object->method('location')
      ->will($this->returnCallback(array($this, 'callbackLocation')));

    $object->method('microtime')
      ->willReturn(0);

    $output = $object->closestZipCode($my_zip, $all_zips);
    if ($output != $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }
    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testClosestZipCode().
   */
  public function providerClosestZipCode() {
    return [
      [
        'message' => 'basic test',
        'my_zip' => "0123",
        'all_zips' => ["0234", "0456"],
        'expected' => [
          'errors' => [],
          'zip' => '0123',
          'lat' => 13666.65,
          'lon' => 122999.88,
          'zips' => [
            '0234' => [
              'km' => 25974,
              'miles' => 233766,
              'lat' => 25999.97,
              'lon' => 233999.77,
            ],
            '0456' => [
              'km' => 50616,
              'miles' => 455544,
              'lat' => 50666.62,
              'lon' => 455999.54,
            ],
          ],
          'duration-seconds' => 0,
        ],
      ],
    ];
  }

  /**
   * Test for sortByDistance().
   *
   * @param string $message
   *   The test message.
   * @param array $input
   *   The input.
   * @param array $expected
   *   The expected output.
   *
   * @cover ::sortByDistance
   * @dataProvider providerSortByDistance
   */
  public function testSortByDistance(string $message, array $input, array $expected) {
    $object = $this->getMockBuilder(App::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $output = $object->sortByDistance($input);
    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testSortByDistance().
   */
  public function providerSortByDistance() {
    return [
      [
        'message' => 'Items are sorted',
        'input' => [
          'a' => [
            'km' => 100,
          ],
          'b' => [
            'km' => 100.5,
          ],
        ],
        'expected' => [
          'b' => [
            'km' => 100.5,
          ],
          'a' => [
            'km' => 100,
          ],
        ],
      ],
    ];
  }

}
