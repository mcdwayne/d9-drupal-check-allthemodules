<?php

namespace Drupal\webform_submission_change_history\Tests;

use Drupal\webform_submission_change_history\traits\CommonUtilities;
use PHPUnit\Framework\TestCase;

class DummyClassUsesCommonUtilities {
  use CommonUtilities;
}

/**
 * Test CommonUtilities.
 *
 * @group myproject
 */
class CommonUtilitiesTest extends TestCase {

  /**
   * Test for spliceAfterKey().
   *
   * @param string $message
   *   The test message.
   * @param array $array
   *   The original array.
   * @param string $key
   *   The key.
   * @param array $expected
   *   The expected result.
   *
   * @cover ::spliceAfterKey
   * @dataProvider providerSpliceAfterKey
   */
  public function testSpliceAfterKey(string $message, array $array, string $key, array $expected) {
    $object = $this->getMockBuilder(DummyClassUsesCommonUtilities::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $output = $object->spliceAfterKey($array, $key, [
      'this-is-a' => 'test',
    ]);

    if ($output !== $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output === $expected, $message);
  }

  /**
   * Provider for testSpliceAfterKey().
   */
  public function providerSpliceAfterKey() {
    return [
      [
        'message' => 'no key',
        'array' => [
          'something' => 'hello',
        ],
        'key' => 'whatever',
        'expected' => [
          'something' => 'hello',
        ],
      ],
      [
        'message' => 'key exists, no title',
        'array' => [
          'something' => 'hello',
        ],
        'key' => 'something',
        'expected' => [
          'something' => 'hello',
        ],
      ],
      [
        'message' => 'key exists, has title',
        'array' => [
          'something' => [
            '#title' => 'not empty',
          ],
        ],
        'key' => 'something',
        'expected' => [
          'something' => [
            '#title' => 'not empty',
          ],
          'this-is-a' => 'test',
        ],
      ],
      [
        'message' => 'embedded key exists, has title',
        'array' => [
          'a' => [
            'b' => [
              'something' => [
                '#title' => 'not empty',
              ],
              'something-else' => 'whatever',
            ],
          ],
        ],
        'key' => 'something',
        'expected' => [
          'a' => [
            'b' => [
              'something' => [
                '#title' => 'not empty',
              ],
              'this-is-a' => 'test',
              'something-else' => 'whatever',
            ],
          ],
        ],
      ],
    ];
  }

}
