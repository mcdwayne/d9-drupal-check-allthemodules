<?php

namespace Drupal\Tests\video_embed_twitch\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_twitch
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * Test URL parsing works as expected.
   *
   * @dataProvider urlsWithExpectedIds
   */
  public function testUrlParsing($provider, $url, $expected) {
    $this->assertEquals($expected, $provider::getIdFromInput($url));
  }

  /**
   * A data provider for URL parsing test cases.
   *
   * @return array
   *   An array of test cases.
   */
  public function urlsWithExpectedIds() {
    return [
      // Normal Twitch Channels.
      'Twitch: iFrame https URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\Twitch',
        'https://player.twitch.tv/?channel=dallas',
        'dallas',
      ],
      'Twitch: iFrame http URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\Twitch',
        'http://player.twitch.tv/?channel=dallas',
        'dallas',
      ],
      'Twitch: Normal https URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\Twitch',
        'https://www.twitch.tv/dallas',
        'dallas',
      ],
      'Twitch: Normal http URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\Twitch',
        'http://www.twitch.tv/dallas',
        'dallas',
      ],
      // Twitch Clips.
      'Twitch Clip: https URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\TwitchClip',
        'https://clips.twitch.tv/LongRandomSlugColorSky',
        'LongRandomSlugColorSky',
      ],
      'Twitch Clip: http URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\TwitchClip',
        'http://clips.twitch.tv/LongRandomSlugColorSky',
        'LongRandomSlugColorSky',
      ],
      // Twitch Collections.
      'Twitch Collection: https URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\TwitchCollection',
        'https://www.twitch.tv/collections/Dl5ogz3aLhV2Vg',
        'Dl5ogz3aLhV2Vg',
      ],
      'Twitch Collection: http URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\TwitchCollection',
        'http://twitch.tv/collections/Dl5ogz3aLhV2Vg',
        'Dl5ogz3aLhV2Vg',
      ],
      // Twitch Video.
      'Twitch Video: https URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\TwitchVideo',
        'https://twitch.tv/videos/123456789',
        '123456789',
      ],
      'Twitch Video: http URL' => [
        'Drupal\video_embed_twitch\Plugin\video_embed_field\Provider\TwitchVideo',
        'http://www.twitch.tv/videos/123456789',
        '123456789',
      ],
    ];
  }

}
