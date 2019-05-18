<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\VariableTest.
 */

namespace Drupal\Tests\elastic_search\Unit\Utility;

use Drupal\elastic_search\Utility\ElasticMappingDumper;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Test variable export functionality in Variable component.
 *
 * @group elastic_search
 */
class ElasticMappingDumperTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Data provider for testExport().
   *
   * @return array
   *   An array containing:
   *     - The expected export string.
   *     - The variable to export.
   */
  public function providerTestExport() {
    return [
      // Array.
      [
        '[]',
        [],
      ],
      [
        // non-associative.
        "[\n  1,\n  2,\n  3,\n  4,\n]",
        [1, 2, 3, 4],
      ],
      [
        // associative.
        "[\n  'a' => 1,\n]",
        ['a' => 1],
      ],
      // Bool.
      [
        'TRUE',
        TRUE,
      ],
      [
        'FALSE',
        FALSE,
      ],
      // Strings.
      [
        "'string'",
        'string',
      ],
      [
        '"\n\r\t"',
        "\n\r\t",
      ],
      [
        // 2 backslashes. \\
        "'\\'",
        '\\',
      ],
      [
        // Double-quote "
        "'\"'",
        "\"",
      ],
      [
        // Single-quote '
        '"\'"',
        "'",
      ],
      [
        // Quotes with $ symbols.
        '"\$settings[\'foo\']"',
        '$settings[\'foo\']',
      ],
      // Object.
      [
        // A stdClass object.
        '(object) []',
        new \stdClass(),
      ],
      [
        // A not-stdClass object.
        "Drupal\\Tests\\elastic_search\\Unit\\Utility\\StubVariableTestClass::__set_state(array(\n))",
        new StubVariableTestClass(),
      ],
    ];
  }

  /**
   * Tests exporting variables.
   *
   * @param string $expected
   *   The expected exported variable.
   * @param mixed  $variable
   *   The variable to be exported.
   *
   * @dataProvider providerTestExport
   */
  public function testExport($expected, $variable) {
    $this->assertEquals($expected, ElasticMappingDumper::export($variable));
  }

}

/**
 * No-op test class for VariableTest::testExport().
 *
 * @see Drupal\Tests\elastic_search\Unit\Utility\ElasticMappingDumperTest::testExport()
 * @see Drupal\Tests\elastic_search\Unit\Utility\ElasticMappingDumperTest::providerTestExport()
 */
class StubVariableTestClass {

}
