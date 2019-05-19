<?php

namespace Drupal\Tests\twitter_api\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the entity expander.
 *
 * @group twitter_api
 */
class TwitterApiEntityExpanderTest extends KernelTestBase {

  /**
   * The expander service.
   *
   * @var \Drupal\twitter_api\TwitterApiEntityExpander
   */
  protected $expander;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'twitter_api',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->expander = \Drupal::service('twitter_api.entity_expander');
  }

  /**
   * Tests the expandUrls function.
   */
  public function testExpandUrls() {
    // Example structure of a tweet with a url entity.
    $tweet = [
      'entities' => [
        'urls' => [
          [
            'url' => 'https://t.co/Vfe5BIiYt5',
            'expanded_url' => 'https://twitter.com',
            'display_url' => 'twitter.com',
            'indicies' => [
              21,
              44,
            ],
          ],
        ],
      ],
    ];

    $actual = $this->expander->expandUrls($tweet);
    $this->assertEquals('<a href="https://twitter.com">twitter.com</a>', $actual['https://t.co/Vfe5BIiYt5']->toString());
  }

  /**
   * Tests the expandImages function.
   */
  public function testExpandImages() {
    // Example structure of a tweet with a media entity.
    $tweet = [
      'entities' => [
        'media' => [
          [
            'type' => 'photo',
            'url' => 'https://t.co/zmrNjq4kqU',
            'media_url_https' => 'https://pbs.twimg.com/media/DBl1V26UwAAKy3G.jpg',
          ],
        ],
      ],
    ];

    $actual = $this->expander->expandImages($tweet);
    $this->assertEquals('https://pbs.twimg.com/media/DBl1V26UwAAKy3G.jpg', $actual['https://t.co/zmrNjq4kqU']);
  }

}
