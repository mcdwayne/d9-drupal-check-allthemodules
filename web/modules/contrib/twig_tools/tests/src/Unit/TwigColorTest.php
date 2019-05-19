<?php

namespace Drupal\Tests\twig_tools\Unit;

use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Tests\UnitTestCase;
use Drupal\twig_tools\TwigExtension\TwigColor;

/**
 * Tests to ensure color convert filters work correctly.
 *
 * @group twig_tools
 *
 * @coversDefaultClass \Drupal\twig_tools\TwigExtension\TwigColor
 */
class TwigColorTest extends UnitTestCase {

  /**
   * Create a new TwigExtension object.
   */
  public function setUp() {
    parent::setUp();

    $loader = new StringLoader();
    $this->twig = new \Twig_Environment($loader);

    $twigTools = new TwigColor();
    $this->twig->addExtension($twigTools);
  }

  /**
   * @covers ::rgbToHex
   *
   * @dataProvider providerTestRgbToHexValues
   */
  public function testRgbToHex($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testMd5Value.
   *
   * @return array
   *   An array of test data with twig values and their hex equivalents.
   */
  public function providerTestRgbToHexValues() {
    return [
      ["{{ [255, 255, 255]|rgb_to_hex }}", '#ffffff'],
      ["{{ [160, 198, 119]|rgb_to_hex }}", '#a0c677'],
      ["{{ [142, 47, 47]|rgb_to_hex }}", '#8e2f2f'],
      ["{{ [0, 0, 0]|rgb_to_hex }}", '#000000'],
      ["{{ [-1, 0, 1]|rgb_to_hex }}", ''],
      ["{{ [-142, -47, -47]|rgb_to_hex }}", ''],
      ["{{ [256, 256, 256]|rgb_to_hex }}", ''],
      ["{{ [1000, 1000, 1000]|rgb_to_hex }}", ''],
      ["{{ [160, 198, 119, 233]|rgb_to_hex }}", ''],
      ["{{ [160, 198]|rgb_to_hex }}", ''],
      ["{{ [160]|rgb_to_hex }}", ''],
    ];

  }

  /**
   * @covers ::cssRgbToHex
   *
   * @dataProvider providerTestCssRgbToHexValues
   */
  public function testCssRgbToHex($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testCssRgbToHex.
   *
   * @return array
   *   An array of test data with twig values and their hex hash equivalents.
   */
  public function providerTestCssRgbToHexValues() {
    return [
      ["{{ 'rgb(255, 255, 255)'|css_rgb_to_hex }}", '#ffffff'],
      ["{{ 'rgb(255,255,255)'|css_rgb_to_hex }}", '#ffffff'],
      ["{{ 'style=\"rgb(255, 255, 255)\"'|css_rgb_to_hex }}", '#ffffff'],
      ["{{ 'rgb(160, 198, 119)'|css_rgb_to_hex }}", '#a0c677'],
      ["{{ 'rgb(142, 47, 47)'|css_rgb_to_hex }}", '#8e2f2f'],
      ["{{ 'rgb(-142, -47, -47)'|css_rgb_to_hex }}", ''],
      ["{{ 'rgb(0, 0, 0)'|css_rgb_to_hex }}", '#000000'],
      ["{{ 'rgb(9, 10, 11)'|css_rgb_to_hex }}", '#090a0b'],
      ["{{ 'rgb(99, 100, 101)'|css_rgb_to_hex }}", '#636465'],
      ["{{ 'rgb(199, 200, 201)'|css_rgb_to_hex }}", '#c7c8c9'],
      ["{{ 'rgb(256, 256, 256)'|css_rgb_to_hex }}", ''],
      ["{{ 'rgb(1000,1000,1000)'|css_rgb_to_hex }}", ''],
      ["{{ 'rgb(0, 0, 0, 0)'|css_rgb_to_hex }}", ''],
    ];
  }

  /**
   * @covers ::hexToRgb
   *
   * @dataProvider providerTestHexToRgbValues
   */
  public function testHexToRgb($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for hexToRgbTest.
   *
   * @return array
   *   An array of test data with twig values and their hex equivalents.
   */
  public function providerTestHexToRgbValues() {
    return [
      ["{{ '#ffffff'|hex_to_rgb|join('-') }}", '255-255-255'],
      ["{{ '#FFFFFF'|hex_to_rgb|join('-') }}", '255-255-255'],
      ["{{ '#a0c677'|hex_to_rgb|join('-') }}", '160-198-119'],
      ["{{ '#8e2f2f'|hex_to_rgb|join('-') }}", '142-47-47'],
      ["{{ '#090a0b'|hex_to_rgb|join('-') }}", '9-10-11'],
      ["{{ '#636465'|hex_to_rgb|join('-') }}", '99-100-101'],
      ["{{ '#c7c8c9'|hex_to_rgb|join('-') }}", '199-200-201'],
      ["{{ '#000000'|hex_to_rgb|join('-') }}", '0-0-0'],
      ["{{ '#fff'|hex_to_rgb|join('-') }}", '255-255-255'],
      ["{{ '#FFF'|hex_to_rgb|join('-') }}", '255-255-255'],
      ["{{ '#zzzzzz'|hex_to_rgb|join('-') }}", ''],
      ["{{ '#ffffffff'|hex_to_rgb|join('-') }}", ''],
    ];
  }

  /**
   * @covers ::hexToCssRgb
   *
   * @dataProvider providerTestHexToCssRgbValues
   */
  public function testHexToCssRgb($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for hexToRgbTest.
   *
   * @return array
   *   An array of test data with twig values and their hex equivalents.
   */
  public function providerTestHexToCssRgbValues() {
    return [
      ["{{ '#ffffff'|hex_to_css_rgb }}", 'rgb(255, 255, 255)'],
      ["{{ '#FFFFFF'|hex_to_css_rgb }}", 'rgb(255, 255, 255)'],
      ["{{ '#a0c677'|hex_to_css_rgb }}", 'rgb(160, 198, 119)'],
      ["{{ '#8e2f2f'|hex_to_css_rgb }}", 'rgb(142, 47, 47)'],
      ["{{ '#090a0b'|hex_to_css_rgb }}", 'rgb(9, 10, 11)'],
      ["{{ '#636465'|hex_to_css_rgb }}", 'rgb(99, 100, 101)'],
      ["{{ '#c7c8c9'|hex_to_css_rgb }}", 'rgb(199, 200, 201)'],
      ["{{ '#000000'|hex_to_css_rgb }}", 'rgb(0, 0, 0)'],
      ["{{ '#fff'|hex_to_css_rgb }}", 'rgb(255, 255, 255)'],
      ["{{ '#FFF'|hex_to_css_rgb }}", 'rgb(255, 255, 255)'],
      ["{{ '#zzzzzz'|hex_to_css_rgb }}", ''],
      ["{{ '#ffffffff'|hex_to_css_rgb }}", ''],
    ];

  }

  /**
   * Unset the test object.
   */
  public function tearDown() {
    unset($this->twigTools, $this->twig);
  }

}
