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
class ParseLinkSampleTest extends UnitTestCase {

  /**
   * Tests boolean query parameters.
   *
   * @dataProvider dataProvider
   */
  public function testSample($sample, $entity_ids, $entity_type = NULL, $entity_id = NULL) {
    $request_context = FillPdfLinkManipulator::parseLink($this->link($sample, $entity_ids, $entity_type, $entity_id));

    // Test '&fid=' is set.
    $this->assertEquals(1, $request_context['fid']);

    // Test '&entity_ids=' is only set if '&sample=' isn't.
    if ($request_context['sample']) {
      $this->assertEmpty($request_context['entity_ids']);
    }
    else {
      $expected = [
        'node' => ['1' => '1'],
      ];
      if (is_array($entity_ids) && count($entity_ids) == 2) {
        $expected['node']['2'] = '2';
      }
      $this->assertEquals($expected, $request_context['entity_ids']);
    }
  }

  /**
   * Input helper for testBooleanFlags().
   */
  public function link($sample, $entity_ids, $entity_type = NULL, $entity_id = NULL) {
    $query = [
      'fid' => 1,
      'sample' => $sample,
    ];

    if (!empty($entity_ids)) {
      $query['entity_ids'] = $entity_ids;
    }
    if (!empty($entity_type)) {
      $query['entity_type'] = $entity_type;
    }
    if (!empty($entity_id)) {
      $query['entity_id'] = $entity_id;
    }

    return Url::fromRoute('fillpdf.populate_pdf', [], ['query' => $query]);
  }

  /**
   * Data provider for testSample().
   *
   * @todo Mock FillPdfForm::load() so we can also test default entities.
   */
  public function dataProvider() {
    return [
      ['true',  ['node:1']],
      ['false', ['node:1']],
      ['true',  ['node:1', 'node:2']],
      ['false', ['node:1', 'node:2']],
      ['true',  NULL, 'node', '1'],
      ['false', NULL, 'node', '1'],
    ];
  }

}
