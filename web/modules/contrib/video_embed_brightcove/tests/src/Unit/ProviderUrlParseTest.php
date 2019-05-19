<?php

namespace Drupal\Tests\video_embed_brightcove\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_brightcove\Plugin\video_embed_field\Provider\Brightcove;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_brightcove
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * Test URL parsing works as expected.
   *
   * @dataProvider urlsWithExpectedIds
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Brightcove::getIdFromInput($url));
  }

  /**
   * A data provider for URL parsing test cases.
   *
   * @return array
   *   An array of test cases.
   */
  public function urlsWithExpectedIds() {
    return [
      'Studio browser preview URL' => [
        'http://players.brightcove.net/4792245499001/default_default/index.html?videoId=4792919782001',
        '4792919782001',
      ],
    ];
  }

}
