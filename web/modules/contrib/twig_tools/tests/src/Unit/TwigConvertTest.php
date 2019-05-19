<?php

namespace Drupal\Tests\twig_tools\Unit;

use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Tests\UnitTestCase;
use Drupal\twig_tools\TwigExtension\TwigConvert;

/**
 * Tests to ensure conversions filters work correctly.
 *
 * @group twig_tools
 *
 * @coversDefaultClass \Drupal\twig_tools\TwigExtension\TwigConvert
 */
class TwigConvertTest extends UnitTestCase {

  /**
   * Create a new TwigExtension object.
   */
  public function setUp() {
    parent::setUp();

    $loader = new StringLoader();
    $this->twig = new \Twig_Environment($loader);

    $twigTools = new TwigConvert();
    $this->twig->addExtension($twigTools);
  }

  /**
   * @covers ::booleanValue
   *
   * @dataProvider providerTestBooleanValues
   */
  public function testBooleanValue($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testBooleanValue.
   *
   * @return array
   *   An array of test data and their boolean equivalents.
   */
  public function providerTestBooleanValues() {
    return [
      ["{{ 0|boolean is same as (false) ? 'false' : 'true' }}", "false"],
      ["{{ 42|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ 0.0|boolean is same as (false) ? 'false' : 'true' }}", "false"],
      ["{{ -1|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ 4.2|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ ''|boolean is same as (false) ? 'false' : 'true' }}", "false"],
      ["{{ 'string'|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ 'true'|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ 'false'|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ '0'|boolean is same as (false) ? 'false' : 'true' }}", "false"],
      ["{{ '1'|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ [1, 2]|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ [0]|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ [0, 0]|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ []|boolean is same as (false) ? 'false' : 'true' }}", "false"],
      ["{{ false|boolean is same as (false) ? 'false' : 'true' }}", "false"],
      ["{{ true|boolean is same as (false) ? 'false' : 'true' }}", "true"],
      ["{{ null|boolean is same as (false) ? 'false' : 'true' }}", "false"],
    ];
  }

  /**
   * @covers ::integerValue
   *
   * @dataProvider providerTestIntegerValues
   */
  public function testIntegerValue($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testIntegerValue.
   *
   * @return array
   *   An array of test data and their integer equivalents.
   */
  public function providerTestIntegerValues() {
    return [
      ["{{ 42|integer }}", '42'],
      ["{{ 4.2|integer }}", '4'],
      ["{{ '42'|integer }}", '42'],
      ["{{ '+42'|integer }}", '42'],
      ["{{ '-42'|integer }}", '-42'],
      ["{{ 042|integer }}", '42'],
      ["{{ '042'|integer }}", '42'],
      ["{{ 42000000|integer }}", '42000000'],
      ["{{ []|integer }}", '0'],
      ["{{ ['foo', 'bar']|integer }}", '1'],
      ["{{ FALSE|integer }}", '0'],
      ["{{ TRUE|integer }}", '1'],
      ["{{ NULL|integer }}", '0'],
      ["{{ 0|integer }}", '0'],
      ["{{ 1|integer }}", '1'],
      ["{{ 0.0|integer }}", '0'],
      ["{{ 1.0|integer }}", '1'],
    ];

  }

  /**
   * @covers ::floatValue
   *
   * @dataProvider providerTestFloatValues
   */
  public function testFloatValue($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testFloatValue.
   *
   * @return array
   *   An array of test data and their float equivalents.
   */
  public function providerTestFloatValues() {
    return [
      ["{{ 42|float }}", '42'],
      ["{{ 4.2|float }}", '4.2'],
      ["{{ 0.42|float }}", '0.42'],
      ["{{ 42000000.00|float }}", '42000000'],
      ["{{ 42.0000000|float }}", '42'],
      ["{{ -42.0000000|float }}", '-42'],
      ["{{ +42.0000000|float }}", '42'],
      ["{{ 42.00000001|float }}", '42.00000001'],
      ["{{ 0000042.00000001|float }}", '42.00000001'],
      ["{{ '42.00000001The'|float }}", '42.00000001'],
      ["{{ 'The42.00000001'|float }}", '0'],
      ["{{ '42'|float }}", '42'],
      ["{{ '+42'|float }}", '42'],
      ["{{ '-42'|float }}", '-42'],
      ["{{ 042|float }}", '42'],
      ["{{ '042'|float }}", '42'],
      ["{{ 42000000|float }}", '42000000'],
      ["{{ []|float }}", '0'],
      ["{{ ['foo', 'bar']|float }}", '1'],
      ["{{ FALSE|float }}", '0'],
      ["{{ TRUE|float }}", '1'],
      ["{{ NULL|float }}", '0'],
      ["{{ 0|float }}", '0'],
      ["{{ 1|float }}", '1'],
      ["{{ 0.0|float }}", '0'],
      ["{{ 1.0|float }}", '1'],
    ];

  }

  /**
   * @covers ::stringValue
   *
   * @dataProvider providerTestStringValues
   */
  public function testStringValue($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testStringValue.
   *
   * @return array
   *   An array of test data and their string equivalents.
   */
  public function providerTestStringValues() {
    return [
      ["{{ 42|string }}", "42"],
      ["{{ 4.2|string }}", "4.2"],
      ["{{ '42'|string }}", "42"],
      ["{{ '+42'|string }}", "+42"],
      ["{{ '-42'|string }}", "-42"],
      ["{{ 042|string }}", "42"],
      ["{{ '042'|string }}", "042"],
      ["{{ 42000000|string }}", "42000000"],
      ["{{ FALSE|string }}", ""],
      ["{{ TRUE|string }}", "1"],
      ["{{ NULL|string }}", ""],
      ["{{ 0|string }}", "0"],
      ["{{ 1|string }}", "1"],
      ["{{ 0.0|string }}", "0"],
      ["{{ 1.0|string }}", "1"],
    ];

  }

  /**
   * @covers ::md5Value
   *
   * @dataProvider providerTestMd5Values
   */
  public function testMd5Value($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testMd5Value.
   *
   * @return array
   *   An array of test data and their md5 hash equivalents.
   */
  public function providerTestMd5Values() {
    return [
      ["{{ '42'|md5 }}", 'a1d0c6e83f027327d8461063f4ac58a6'],
      ["{{ '4.2'|md5 }}", '8653d5c7898950016e5d019df6815626'],
      ["{{ '+42'|md5 }}", 'deda8ddbf790f3682d5cf69d237bb0b2'],
      ["{{ '-42'|md5 }}", '8dfcb89fd8620e3e7fb6a03a53f307dc'],
      ["{{ '42'|md5 }}", 'a1d0c6e83f027327d8461063f4ac58a6'],
      ["{{ 'Test'|md5 }}", '0cbc6611f5540bd0809a388dc95a615b'],
      ["{{ '0'|md5 }}", 'cfcd208495d565ef66e7dff9f98764da'],
      ["{{ '0'|md5 }}", 'cfcd208495d565ef66e7dff9f98764da'],
      ["{{ '1'|md5 }}", 'c4ca4238a0b923820dcc509a6f75849b'],
    ];
  }

  /**
   * @covers ::jsonDecode
   *
   * @dataProvider providerTestJsonDecodeValues
   */
  public function testJsonDecode($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for jsonDecode.
   *
   * @return array
   *   An array of test JSON strings and their rendered equivalents.
   */
  public function providerTestJsonDecodeValues() {
    return [
      ['{% set json = \'{
        "a": 1,
        "b": 2,
        "c": 3,
        "d": 4,
        "e": 5
      }\'|json_decode(true) %}{{ json|join(",") }}', '1,2,3,4,5',
      ],
      ['{% set json = \'{
        "aliceblue": "#f0f8ff",
        "antiquewhite": "#faebd7",
        "aqua": "#00ffff",
        "aquamarine": "#7fffd4",
        "azure": "#f0ffff",
        "beige": "#f5f5dc",
        "bisque": "#ffe4c4",
        "black": "#000000",
        "blanchedalmond": "#ffebcd",
        "blue": "#0000ff",
        "blueviolet": "#8a2be2",
        "brown": "#a52a2a"
      }\'|json_decode %}{{ json|join(", ") }}', '#f0f8ff, #faebd7, #00ffff, #7fffd4, #f0ffff, #f5f5dc, #ffe4c4, #000000, #ffebcd, #0000ff, #8a2be2, #a52a2a',
      ],
      ['{% set json = \'{
        "string": "string",
        "boolean_true": true,
        "boolean_false": false,
        "integer": 42,
        "float": 4.2
      }\'|json_decode %}{{ json|join(", ") }}', 'string, 1, , 42, 4.2',
      ],
    ];

  }

  /**
   * Unset the test object.
   */
  public function tearDown() {
    unset($this->twigTools, $this->twig);
  }

}
