<?php

/**
 * @file
 * Contains Drupal\Tests\video_embed_instagram\Unit\ProviderUrlParseTest.
 */

namespace Drupal\Tests\video_embed_instagram\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_instagram\Plugin\video_embed_field\Provider\Instagram;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_instagram
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * @dataProvider urlsWithExpectedIds
   *
   * Test URL parsing works as expected.
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Instagram::getIdFromInput($url));
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
        'https://www.instagram.com/p/BDAtHPYSeO4/',
        'BDAtHPYSeO4',
      ],
      [
        'http://www.instagram.com/p/BDAtHPYSeO4/',
        'BDAtHPYSeO4',
      ],
      [
        'http://www.instagram.com/p/BDAtHPYSeO4',
        'BDAtHPYSeO4',
      ],
      [
        'https://www.instagram.com/p/BDAtHPYSeO4',
        'BDAtHPYSeO4',
      ],
      [
        'https://www.instagram.com/p/BFElYdqjJwa/?taken-by=9gag',
        'BFElYdqjJwa',
      ],
    ];
  }
}
