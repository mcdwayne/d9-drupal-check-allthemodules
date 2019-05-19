<?php

namespace Drupal\Tests\video_embed_spotify\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_spotify\Plugin\video_embed_field\Provider\Spotify;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_spotify
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * Test URL parsing works as expected.
   *
   * @dataProvider urlsWithExpectedIds
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Spotify::getIdFromInput($url));
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
        'https://open.spotify.com/user/spotify/playlist/2PXdUld4Ueio2pHcB6sM8j',
        'user/spotify/playlist/2PXdUld4Ueio2pHcB6sM8j',
      ],
    ];
  }

}
