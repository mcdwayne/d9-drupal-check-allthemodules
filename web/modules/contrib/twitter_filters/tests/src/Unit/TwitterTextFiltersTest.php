<?php

namespace Drupal\Tests\twitter_filters\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\twitter_filters\TwitterTextFilters;

/**
 * @coversDefaultClass \Drupal\twitter_filters\TwitterTextFilters
 * @group twitter_filters
 */
class TwitterTextFiltersTest extends UnitTestCase {

  /**
   * TwitterTextFilters service.
   *
   * @var \Drupal\twitter_filters\TwitterTextFilters
   */
  public $twitterTextFiltersService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->twitterTextFiltersService = new TwitterTextFilters();
  }

  /**
   * Check if username links are properly generated.
   *
   * @dataProvider twitterUsernameProvider
   */
  public function testTwitterFilterTextUsernames($text, $expected) {
    $prefix = '@';
    $destination = 'https://twitter.com/';
    $this->assertEquals($expected, $this->twitterTextFiltersService->twitterFilterText($text, $prefix, $destination, 'twitter-atreply'));
  }

  /**
   * Check if hashtag links are properly generated.
   *
   * @dataProvider twitterHashtagProvider
   */
  public function testTwitterFilterTextHashtags($text, $expected) {
    $prefix = '#';
    $destination = 'https://twitter.com/search?q=%23';
    $this->assertEquals($expected, $this->twitterTextFiltersService->twitterFilterText($text, $prefix, $destination, 'twitter-hashtag'));
  }

  /**
   * Data provider for testTwitterFilterTextUsernames.
   */
  public function twitterUsernameProvider() {
    return [
      ['@username', '<a href="https://twitter.com/username" class="twitter-atreply">@username</a>'],
      ['@1username', '<a href="https://twitter.com/1username" class="twitter-atreply">@1username</a>'],
      ['@username1', '<a href="https://twitter.com/username1" class="twitter-atreply">@username1</a>'],
      ['@UserName', '<a href="https://twitter.com/UserName" class="twitter-atreply">@UserName</a>'],
    ];
  }

  /**
   * Data provider for testTwitterFilterTextHashtags.
   */
  public function twitterHashtagProvider() {
    return [
      ['#hashtag', '<a href="https://twitter.com/search?q=%23hashtag" class="twitter-hashtag">#hashtag</a>'],
      ['#HashTag', '<a href="https://twitter.com/search?q=%23HashTag" class="twitter-hashtag">#HashTag</a>'],
      ['#1HashTag1', '<a href="https://twitter.com/search?q=%231HashTag1" class="twitter-hashtag">#1HashTag1</a>'],
    ];
  }

}
