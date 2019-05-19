<?php

/**
 * @file
 * Contains Drupal\Tests\video_embed_vine\Unit\ProviderUrlParseTest.
 */

namespace Drupal\Tests\video_embed_vine\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_vine\Plugin\video_embed_field\Provider\Vine;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_vine
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * @dataProvider urlsWithExpectedIds
   *
   * Test URL parsing works as expected.
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Vine::getIdFromInput($url));
  }

  /**
   * A data provider for URL parsing test cases.
   *
   * @return array
   *   An array of test cases.
   */
  public function urlsWithExpectedIds() {
    return [
      [
        'https://vine.co/v/i6gtpp0OrjA',
        'i6gtpp0OrjA',
      ],
      [
        'https://vine.co/v/not_a_real_id',
        FALSE,
      ],
      [
        'https://vine.co/v/a-page',
        FALSE,
      ],
      [
        'vine.co/v/a-page',
        FALSE,
      ],
    ];
  }
}
