<?php

namespace Drupal\Tests\fillpdf\Unit\LinkManipulator;

use Drupal\fillpdf\Service\FillPdfLinkManipulator;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\fillpdf\Service\FillPdfLinkManipulator::parseLink
 *
 * @group fillpdf
 */
class ParseLinkBooleansTest extends UnitTestCase {

  /**
   * Tests &sample=, &download= and &flatten= query parameters.
   *
   * @dataProvider dataProvider
   */
  public function testBooleans($input, $expected) {
    $request_context = FillPdfLinkManipulator::parseLink($this->link($input));

    $this->assertEquals(is_null($expected) ? FALSE : $expected, $request_context['sample']);

    $this->assertEquals(is_null($expected) ? FALSE : $expected, $request_context['force_download']);

    $this->assertEquals(is_null($expected) ? TRUE : $expected, $request_context['flatten']);
  }

  /**
   * Input helper for testBooleans().
   */
  public function link($input) {
    return Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => 1,
        'entity_type' => 'node',
        'entity_id' => 1,
        'sample' => $input,
        'download' => $input,
        'flatten' => $input,
      ],
    ]);
  }

  /**
   * Data provider for testBooleans().
   */
  public function dataProvider() {
    return [
      ['1', TRUE],
      ['true', TRUE],
      ['True', TRUE],
      ['TRUE', TRUE],
      ['on', TRUE],
      ['On', TRUE],
      ['ON', TRUE],
      ['yes', TRUE],
      ['Yes', TRUE],
      ['YES', TRUE],

      ['0', FALSE],
      ['false', FALSE],
      ['False', FALSE],
      ['FALSE', FALSE],
      ['off', FALSE],
      ['Off', FALSE],
      ['OFF', FALSE],
      ['no', FALSE],
      ['No', FALSE],
      ['NO', FALSE],

      // These three are important, so should always be obeyed:
      ['', NULL],
      ['foo', NULL],
      ['bar', NULL],

      // The following ones are less fortunate, so may be refactored:
      ['-1', NULL],
      ['2', NULL],
      ['y', NULL],
      ['Y', NULL],
      ['n', NULL],
      ['N', NULL],
    ];
  }

}
