<?php

namespace Drupal\Tests\video_embed_vkontakte\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_vkontakte\Plugin\video_embed_field\Provider\Vkontakte;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_vkontakte
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * @dataProvider urlsWithExpectedIds
   *
   * Test URL parsing works as expected.
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Vkontakte::getIdFromInput($url));
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
        '<iframe src="//vk.com/video_ext.php?oid=306881605&id=456239031&hash=52c8c3d16a5ca3f7&hd=2" width="853" height="480" frameborder="0" allowfullscreen></iframe>',
        'https://vk.com/video306881605_456239031',
      ],
      [
        'https://vk.com/video287593292_456239020',
        'https://vk.com/video287593292_456239020',
      ]
    ];
  }
}
