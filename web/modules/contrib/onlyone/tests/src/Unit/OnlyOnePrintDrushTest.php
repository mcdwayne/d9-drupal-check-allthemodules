<?php

namespace Drupal\Tests\onlyone\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\onlyone\OnlyOnePrintDrush;
use Drupal\Tests\onlyone\Traits\OnlyOneUnitTestTrait;

/**
 * Tests the OnlyOnePrintAdminPage class methods.
 *
 * @group onlyone
 * @coversDefaultClass \Drupal\onlyone\OnlyOnePrintDrush
 */
class OnlyOnePrintDrushTest extends UnitTestCase {

  use OnlyOneUnitTestTrait;

  /**
   * Tests the content type list for printing for the Admin Page.
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $content_types
   *   A list with content types objects.
   *
   * @covers ::getContentTypesListForPrint
   * @dataProvider providerGetContentTypesListForPrint
   */
  public function testGetContentTypesListForPrint(array $expected, array $content_types) {
    // Creating the object.
    $onlyOnePrintDrush = new OnlyOnePrintDrush();
    $onlyOnePrintDrush->setStringTranslation($this->getStringTranslationStub());

    // Testing the function.
    $this->assertEquals($expected, $onlyOnePrintDrush->getContentTypesListForPrint($content_types));
  }

  /**
   * Data provider for testGetContentTypesListForPrint().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getContentTypesListForPrint().
   *   - 'content_types' - A list with content types objects.
   *
   * @see getContentTypesListForPrint()
   */
  public function providerGetContentTypesListForPrint() {

    // Getting the list of content types.
    $content_types = $this->getContentTypesObjectList();

    // Adding format to the Configured string.
    $configured = sprintf(OnlyOnePrintDrush::GREEN_OUTPUT, 'Configured');

    $expected = [
      // Test 1.
      [
        'page' => 'Basic Page (En: 1 Node, Es: 1 Node) ' . $configured,
        'blog' => 'Blog Post (0 Nodes) ' . $configured,
        'car' => 'Car (Not specified: 1 Node, Not applicable: 2 Nodes, En: 1 Node)',
        'article' => 'Article (Not specified: 1 Node, En: 2 Nodes, Es: 1 Node)',
      ],
      // Test 2.
      [
        'blog' => 'Blog Post (En: 1 Node) ' . $configured,
        'car' => 'Car (0 Nodes)',
      ],
      // Test 3.
      [
        'page' => 'Basic Page (En: 1 Node, Es: 1 Node) ' . $configured,
        'car' => 'Car (0 Nodes) ' . $configured,
        'article' => 'Article (Es: 3 Nodes)',
      ],
      // Test 4.
      [
        'page' => 'Basic Page (1 Node) ' . $configured,
        'blog' => 'Blog Post (2 Nodes) ' . $configured,
        'car' => 'Car (0 Nodes)',
        'article' => 'Article (5 Nodes)',
      ],
      // Test 5.
      [
        'blog' => 'Blog Post (0 Nodes) ' . $configured,
        'car' => 'Car (1 Node)',
      ],
      // Test 6.
      [
        'page' => 'Basic Page (1 Node) ' . $configured,
        'car' => 'Car (5 Nodes) ' . $configured,
        'article' => 'Article (3 Nodes)',
      ],
    ];

    $tests['multilingual 1'] = [$expected[0], $content_types[0]];
    $tests['multilingual 2'] = [$expected[1], $content_types[1]];
    $tests['multilingual 3'] = [$expected[2], $content_types[2]];
    $tests['non-multilingual 1'] = [$expected[3], $content_types[3]];
    $tests['non-multilingual 2'] = [$expected[4], $content_types[4]];
    $tests['non-multilingual 3'] = [$expected[5], $content_types[5]];

    return $tests;
  }

}
