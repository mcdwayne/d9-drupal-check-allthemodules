<?php

namespace Drupal\twitter_filter\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\twitter_filter\Plugin\Filter\TwitterFilter;

/**
 * Tests Twitter Filter functions.
 *
 * @group TwitterFilter
 */
class TwitterFilterTest extends UnitTestCase {

  /**
   * Test info.
   */
  public static function getInfo() {
    return array(
      'name' => 'Twitter Filter module text filters',
      'description' => 'Tests raw filtering functions.',
    );
  }

  /**
   * Test twitter_filter_process_hashtag_hashtag_page().
   *
   * @dataProvider providerTwitterFilterProcessHashtagHashtagPage
   */
  public function testTwitterFilterProcessHashtagHashtagPage($input, $expected) {
    $result = TwitterFilter::processHashtag($input, 'hashtag_page');
    $this->assertEquals($expected, $result);
  }

  /**
   * Test twitter_filter_process_hashtag_search_page().
   *
   * @dataProvider providerTwitterFilterProcessHashtagSearchPage
   */
  public function testTwitterFilterProcessHashtagSearchPage($input, $expected) {
    $result = TwitterFilter::processHashtag($input, 'search_page');
    $this->assertEquals($expected, $result);
  }

  /**
   * Test twitter_filter_process_username_user_page().
   *
   * @dataProvider providerTwitterFilterProcessUsernameUserPage
   */
  public function testTwitterFilterProcessUsernameUserPage($input, $expected) {
    $result = TwitterFilter::processUsername($input, 'user_page');
    $this->assertEquals($expected, $result);
  }

  /**
   * Test twitter_filter_process_username_search_page().
   *
   * @dataProvider providerTwitterFilterProcessUsernameSearchPage
   */
  public function testTwitterFilterProcessUsernameSearchPage($input, $expected) {
    $result = TwitterFilter::processUsername($input, 'search_page');
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testTwitterFilterProcessHashtagHashtagPage().
   */
  public static function providerTwitterFilterProcessHashtagHashtagPage() {
    return array(
      array(
        self::providerMessage(),
        '@username says <a class="twitter-hashtag" href="https://twitter.com/hashtag/hello">#hello</a>.',
      ),
    );
  }

  /**
   * Data provider for testTwitterFilterProcessHashtagSearchPage().
   */
  public static function providerTwitterFilterProcessHashtagSearchPage() {
    return array(
      array(
        self::providerMessage(),
        '@username says <a class="twitter-hashtag" href="https://twitter.com/search?q=%23hello">#hello</a>.',
      ),
    );
  }

  /**
   * Data provider for testTwitterFilterProcessUsernameUserPage().
   */
  public static function providerTwitterFilterProcessUsernameUserPage() {
    return array(
      array(
        self::providerMessage(),
        '<a class="twitter-username" href="https://twitter.com/username">@username</a> says #hello.',
      ),
    );
  }

  /**
   * Data provider for testTwitterFilterProcessUsernameSearchPage().
   */
  public static function providerTwitterFilterProcessUsernameSearchPage() {
    return array(
      array(
        self::providerMessage(),
        '<a class="twitter-username" href="https://twitter.com/search?q=%40username">@username</a> says #hello.',
      ),
    );
  }

  /**
   * Provides a string with an example Twitter message.
   *
   * @return string
   *   An example Twitter message as a string.
   */
  public static function providerMessage() {
    return '@username says #hello.';
  }

}
