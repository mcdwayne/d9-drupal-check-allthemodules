<?php

namespace Drupal\Tests\onlyone\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\onlyone\OnlyOnePrintAdminPage;
use Drupal\Tests\onlyone\Traits\OnlyOneUnitTestTrait;

/**
 * Tests the OnlyOnePrintAdminPage class methods.
 *
 * @group onlyone
 * @coversDefaultClass \Drupal\onlyone\OnlyOnePrintAdminPage
 */
class OnlyOnePrintAdminPageTest extends UnitTestCase {

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
    $onlyOnePrintAdminPage = new OnlyOnePrintAdminPage();

    // Testing the function.
    $this->assertEquals($expected, $onlyOnePrintAdminPage->getContentTypesListForPrint($content_types));
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

    $expected = [
      // Test 1.
      [
        'page' => 'Basic Page <strong>(En: 1 Node, Es: 1 Node)</strong>',
        'blog' => 'Blog Post <strong>(0 Nodes)</strong>',
        'car' => 'Car <strong>(Not specified: 1 Node, Not applicable: 2 Nodes, En: 1 Node)</strong>',
        'article' => 'Article <strong>(Not specified: 1 Node, En: 2 Nodes, Es: 1 Node)</strong>',
      ],
      // Test 2.
      [
        'blog' => 'Blog Post <strong>(En: 1 Node)</strong>',
        'car' => 'Car <strong>(0 Nodes)</strong>',
      ],
      // Test 3.
      [
        'page' => 'Basic Page <strong>(En: 1 Node, Es: 1 Node)</strong>',
        'car' => 'Car <strong>(0 Nodes)</strong>',
        'article' => 'Article <strong>(Es: 3 Nodes)</strong>',
      ],
      // Test 4.
      [
        'page' => 'Basic Page <strong>(1 Node)</strong>',
        'blog' => 'Blog Post <strong>(2 Nodes)</strong>',
        'car' => 'Car <strong>(0 Nodes)</strong>',
        'article' => 'Article <strong>(5 Nodes)</strong>',
      ],
      // Test 5.
      [
        'blog' => 'Blog Post <strong>(0 Nodes)</strong>',
        'car' => 'Car <strong>(1 Node)</strong>',
      ],
      // Test 6.
      [
        'page' => 'Basic Page <strong>(1 Node)</strong>',
        'car' => 'Car <strong>(5 Nodes)</strong>',
        'article' => 'Article <strong>(3 Nodes)</strong>',
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
