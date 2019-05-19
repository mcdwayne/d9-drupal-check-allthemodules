<?php

namespace Drupal\Tests\twig_tools\Unit;

use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Tests\UnitTestCase;
use Drupal\twig_tools\TwigExtension\TwigSanitize;

/**
 * Tests to ensure sanitization filters work correctly.
 *
 * @group twig_tools
 *
 * @coversDefaultClass \Drupal\twig_tools\TwigExtension\TwigSanitize
 */
class TwigSanitizeTest extends UnitTestCase {

  /**
   * Create a new TwigExtension object.
   */
  public function setUp() {
    parent::setUp();

    $loader = new StringLoader();
    $this->twig = new \Twig_Environment($loader);

    $twigTools = new TwigSanitize();
    $this->twig->addExtension($twigTools);
  }

  /**
   * @covers ::cleanClassArray
   *
   * @dataProvider providerTestCleanClassArrayValues
   */
  public function testCleanClassArray($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for providerTestCleanClassArrayValues.
   *
   * @return array
   *   An array of test data their cleaned class values.
   */
  public function providerTestCleanClassArrayValues() {
    return [
      ["{{-
         [
        'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789',
        '¡¢£¤¥',
        'css__identifier__with__double__underscores',
        'invalid !\"#$%&\'()*+,./:;<=>?@[\\]^`{|}~ identifier',
        'block__element--modifier',
      ]|clean_class_array|join(' ') -}}", "abcdefghijklmnopqrstuvwxyz-abcdefghijklmnopqrstuvwxyz-0123456789 ¡¢£¤¥ css__identifier__with__double__underscores invalid---identifier block__element--modifier",
      ],
    ];

  }

  /**
   * @covers ::arrayUnique
   *
   * @dataProvider providerTestArrayUniqueValues
   */
  public function testArrayUnique($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testArrayUnique.
   *
   * @return array
   *   An array of test data and their unique values.
   */
  public function providerTestArrayUniqueValues() {
    return [
      ["{{- [
        '0',
        '1',
        '2',
        '3',
        '0',
        '1',
        NULL,
        FALSE,
        TRUE,
        'Unique',
        'Not Unique',
        'Not Unique',
        0,
        1,
        2,
        0.0,
        1.0,
        2.0,
      ]|array_unique|join(', ') -}}", "0, 1, 2, 3, , Unique, Not Unique",
      ],
    ];
  }

  /**
   * @covers ::removeEmpty
   *
   * @dataProvider providerTestRemoveEmptyValues
   */
  public function testRemoveEmpty($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for providerTestRemoveEmptyValues.
   *
   * @return array
   *   An array of test data and their falsy values.
   */
  public function providerTestRemoveEmptyValues() {
    return [
      ["{{-
         [
        '0',
        '1',
        '2',
        '3',
        '0',
        '1',
        0,
        1,
        2,
        0.0,
        1.0,
        2.0,
        FALSE,
        TRUE,
        NULL,
        'Unique',
        [],
      ]|remove_empty|join(', ') -}}", "1, 2, 3, 1, 1, 2, 1, 2, 1, Unique",
      ],
    ];
  }

  /**
   * @covers ::scrubClassArray
   *
   * @dataProvider providerTestScrubClassArrayValues
   */
  public function testScrubClassArray($template, $expected) {

    $result = $this->twig->render($template);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for testScrubClassArray.
   *
   * @return array
   *   An array of test data and their sanitized values.
   */
  public function providerTestScrubClassArrayValues() {
    return [
      ["{{-
         [
        'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789',
        '¡¢£¤¥',
        'css__identifier__with__double__underscores',
        'invalid !\"#$%&\'()*+,./:;<=>?@[\\]^`{|}~ identifier',
        'block__element--modifier',
        'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789',
        '¡¢£¤¥',
        'css__identifier__with__double__underscores',
        'invalid !\"#$%&\'()*+,./:;<=>?@[\\]^`{|}~ identifier',
        'block__element--modifier',
        '0',
        '1',
        '2',
        '3',
        '0',
        '1',
        NULL,
        FALSE,
        TRUE,
        'Unique',
        'Not Unique',
        'Not Unique',
        0,
        1,
        2,
        0.0,
        1.0,
        2.0,
        '0',
        '1',
        '2',
        '3',
        '0',
        '1',
        0,
        1,
        2,
        0.0,
        1.0,
        2.0,
        FALSE,
        TRUE,
        NULL,
        'Unique',
      ]|scrub_class_array|join(' ') -}}", "abcdefghijklmnopqrstuvwxyz-abcdefghijklmnopqrstuvwxyz-0123456789 ¡¢£¤¥ css__identifier__with__double__underscores invalid---identifier block__element--modifier _ unique not-unique",
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
