<?php

namespace Drupal\Tests\video_embed_facebook\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_facebook\Plugin\video_embed_field\Provider\Facebook;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_facebook
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * @dataProvider urlsWithExpectedIds
   *
   * Test URL parsing works as expected.
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Facebook::getIdFromInput($url));
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
        'https://www.facebook.com/viralthreaddotcom/videos/925673600907816/',
        '925673600907816',
      ],
      [
        'http://www.facebook.com/viralthreaddotcom/videos/925673600907816/',
        '925673600907816',
      ],
      [
        'http://facebook.com/viralthreaddotcom/videos/925673600907816/',
        '925673600907816',
      ],
      [
        'http://facebook.com/viralthreaddotcom/videos/925673600907816',
        '925673600907816',
      ],
      [
        'http://facebook.com/viralthreaddotcom/videos/925673600907816',
        '925673600907816',
      ],
      [
        'https://www.facebook.com/Departement.Gironde/videos/1112970215438894',
        '1112970215438894'
      ],
      [
        'https://www.facebook.com/CoD3Rs/videos/123123',
        '123123',
      ],
      [
        'https://www.facebook.com/video.php?v=925673600907816',
        '925673600907816',
      ],
      [
        'https://www.facebook.com/video.php?v=123ABC',
        FALSE,
      ],
      [
        'https://www.facebook.com/viralthreaddotcom/notvideos/925673600907816/',
        FALSE,
      ],
      [
        'https://www.facebook.com/videos/925673600907816/',
        FALSE,
      ],
      [
        'https://www.facebook.com/925673600907816/',
        FALSE,
      ],
    ];
  }
}
