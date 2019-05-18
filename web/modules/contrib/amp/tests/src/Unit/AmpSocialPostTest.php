<?php

namespace Drupal\Tests\amp\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\amp\Element\AmpSocialPost;

/**
 * @coversDefaultClass \Drupal\amp\Element\AmpSocialPost
 *
 * @group amp_elements
 */
class AmpSocialPostTest extends UnitTestCase {

  /**
   * Allowed providers to use for the test.
   *
   * @var array
   */
  public static $providers = [
    'facebook',
    'twitter',
    'instagram',
    'pinterest',
  ];

  /**
   * @covers ::getProviders
   */
  public function testGetProviders() {
    $processed = AmpSocialPost::getProviders();
    $this->assertTrue(is_array($processed));
    foreach (static::$providers as $name) {
      $this->assertTrue(array_key_exists($name, $processed));
    }
  }

  /**
   * @covers ::getLibraries
   */
  public function testGetLibraries() {
    $processed = AmpSocialPost::getLibraries();
    $this->assertTrue(is_array($processed));
    foreach ($processed as $item) {
      $this->assertEquals(1, preg_match('@([a-z0-9_-]*)(/amp\.)([a-z0-9_-]*)@', $item, $matches));
    }
  }

  /**
   * @covers ::getPatterns
   */
  public function testGetPatterns() {
    $processed = AmpSocialPost::getPatterns();
    $this->assertTrue(is_array($processed));
    foreach (static::$providers as $name) {
      $this->assertTrue(array_key_exists($name, $processed));
    }
  }

  /**
   * @covers ::getProvider
   * @dataProvider providerData
   */
  public function testGetProvider($original, $desired) {
    $processed = AmpSocialPost::getProvider($original);
    $this->assertEquals($processed, $desired);
  }

  /**
   * @covers ::getId
   * @dataProvider idData
   */
  public function testGetId($original, $desired, $provider) {
    $processed = AmpSocialPost::getId($original, $provider);
    $this->assertEquals($processed, $desired);
  }

  /**
   * @covers ::preRenderSocialPost
   * @dataProvider renderData
   */
  public function testpreRenderSocialPost($original, $desired) {
    $processed = AmpSocialPost::preRenderSocialPost($original);
    $this->assertEquals($processed, $desired);
  }

 /**
   * Provides provider data.
   *
   * @return array
   */
  public function providerData() {
    $values = [
      'Facebook - www' => [
        'https://www.facebook.com/ParksCanada/posts/1712989015384373',
        'facebook',
      ],
      'Facebook - no www' => [
        'https://facebook.com/ParksCanada/posts/1712989015384373',
        'facebook',
      ],
      'Facebook - http' => [
        'http://facebook.com/ParksCanada/posts/1712989015384373',
        'facebook',
      ],
      'Twitter - www' => [
        'https://www.twitter.com/cramforce/status/638793490521001985',
        'twitter',
      ],
      'Twitter - no www' => [
        'https://twitter.com/cramforce/status/638793490521001985',
        'twitter',
      ],
      'Twitter - http' => [
        'http://twitter.com/cramforce/status/638793490521001985',
        'twitter',
      ],
      'Pinterest - www' => [
        'https://www.pinterest.com/pin/99360735500167749/',
        'pinterest',
      ],
      'Pinterest - no www' => [
        'https://pinterest.com/pin/99360735500167749/',
        'pinterest',
      ],
      'Pinterest - http' => [
        'https://pinterest.com/pin/99360735500167749/',
        'pinterest',
      ],
      'Instagram - www' => [
        'https://instagram.com/p/fBwFP',
        'instagram',
      ],
      'Instagram - no www' => [
        'https://instagram.com/p/fBwFP',
        'instagram',
      ],
      'Instagram - http' => [
        'http://instagram.com/p/fBwFP',
        'instagram',
      ],
    ];
    return $values;
  }

  /**
   * Provides id data.
   *
   * @return array
   */
  public function idData() {
    $values = [
      'Facebook - www' => [
        'https://www.facebook.com/ParksCanada/posts/1712989015384373',
        '1712989015384373',
        'facebook',
      ],
      'Facebook - no www' => [
        'https://facebook.com/ParksCanada/posts/1712989015384373',
        '1712989015384373',
        'facebook',
      ],
      'Facebook - http' => [
        'http://facebook.com/ParksCanada/posts/1712989015384373',
        '1712989015384373',
        'facebook',
      ],
      'Twitter - www' => [
        'https://www.twitter.com/cramforce/status/638793490521001985',
        '638793490521001985',
        'twitter',
      ],
      'Twitter - no www' => [
        'https://twitter.com/cramforce/status/638793490521001985',
        '638793490521001985',
        'twitter',
      ],
      'Twitter - http' => [
        'http://twitter.com/cramforce/status/638793490521001985',
        '638793490521001985',
        'twitter',
      ],
      'Pinterest - www' => [
        'https://www.pinterest.com/pin/99360735500167749/',
        '99360735500167749',
        'pinterest',
      ],
      'Pinterest - no www' => [
        'https://pinterest.com/pin/99360735500167749/',
        '99360735500167749',
        'pinterest',
      ],
      'Pinterest - http' => [
        'https://pinterest.com/pin/99360735500167749/',
        '99360735500167749',
        'pinterest',
      ],
      'Instagram - www' => [
        'https://instagram.com/p/fBwFP',
        'fBwFP',
        'instagram',
      ],
      'Instagram - no www' => [
        'https://instagram.com/p/fBwFP',
        'fBwFP',
        'instagram',
      ],
      'Instagram - http' => [
        'http://instagram.com/p/fBwFP',
        'fBwFP',
        'instagram',
      ],
    ];
    return $values;
  }

  /**
   * Provides render data.
   *
   * @return array
   */
  public function renderData() {
    $values = [
      'Facebook element' => [
        [
          '#type' => 'amp_social_post',
          '#url' => 'https://www.facebook.com/ParksCanada/posts/1712989015384373',
          '#attributes' => [
            'data-embed-as' => 'post',
            'data-align-center' => 'true',
          ],
        ],
        [
          '#type' => 'amp_social_post',
          '#url' => 'https://www.facebook.com/ParksCanada/posts/1712989015384373',
          '#attributes' => [
            'data-embed-as' => 'post',
            'data-align-center' => 'true',
            'data-href' => 'https://www.facebook.com/ParksCanada/posts/1712989015384373',
          ],
          '#provider' => 'facebook',
          '#attached' => [
            'library' => [
              'amp/amp.facebook',
            ],
          ],
        ],
      ],
    ];
    return $values;
  }

}
