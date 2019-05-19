<?php

namespace Drupal\Tests\video_embed_hulu\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_facebook\Plugin\video_embed_field\Provider\Facebook;
use Drupal\video_embed_hulu\Plugin\video_embed_field\Provider\Hulu;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_hulu
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * @dataProvider urlsWithExpectedIds
   *
   * Test URL parsing works as expected.
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Hulu::getIdFromInput($url));
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
        'http://www.hulu.com/watch/825355',
        '825355',
      ],
      [
        'https://www.hulu.com/watch/825355',
        '825355',
      ],
      [
        'https://hulu.com/watch/825355',
        '825355',
      ],
    ];
  }
}
