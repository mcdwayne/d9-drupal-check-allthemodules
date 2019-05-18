<?php

namespace Drupal\Tests\feed_block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Integration test for a standard RSS version 2.0 feed.
 *
 * @group feed_block
 */
class RssTypesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'block',
    'block_content',
    'link',
    'feed_block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer blocks',
    ]);

    $this->drupalLogin($admin_user);
  }

  /**
   * Test a valid RSS 2.0 feed.
   */
  public function testRss() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('block/add/feed_block');

    $host = \Drupal::request()->getHost();
    $module_path = \Drupal::moduleHandler()->getModule('feed_block')->getPath();

    $rss = 'http://' . $host . '/' . $module_path . '/tests/fixtures/rss.xml';

    $this->submitForm([
      'info[0][value]' => 'Feed Block Test',
      'field_rss_feed[0][feed_uri]' => $rss,
      'field_read_more[0][uri]' => 'https://drupal.org',
      'field_read_more[0][title]' => 'Read more',
    ], 'Save');

    $assert->pageTextContains('Feed Block Feed Block Test has been created.');

    // Place Block in "Content" region on all pages.
    $this->submitForm([
      'region' => 'content',
    ], 'Save block');
    $assert->pageTextContains('The block configuration has been saved.');

    $this->drupalGet('<front>');
    // Verify page output.
    $assert->elementTextContains('css', '#block-feedblocktest h2', 'Feed Block Test');
    $assert->pageTextContains('Mon, 09/07/2009 - 02:20');
    $assert->linkByHrefExists('http://www.example.com/blog/post/1');
    $assert->pageTextContains('Here is some text containing an interesting description.');
    $assert->linkByHrefExists('https://drupal.org');
  }

  /**
   * Test a Youtube-type feed.
   */
  public function testYouTube() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('block/add/feed_block');

    $host = \Drupal::request()->getHost();
    $module_path = \Drupal::moduleHandler()->getModule('feed_block')->getPath();

    $rss = 'http://' . $host . '/' . $module_path . '/tests/fixtures/youtube.xml';

    $this->submitForm([
      'info[0][value]' => 'Feed Block Test',
      'field_rss_feed[0][feed_uri]' => $rss,
      'field_read_more[0][uri]' => 'https://drupal.org',
      'field_read_more[0][title]' => 'Read more',
    ], 'Save');

    $assert->pageTextContains('Feed Block Feed Block Test has been created.');

    // Place Block in "Content" region on all pages.
    $this->submitForm([
      'region' => 'content',
    ], 'Save block');
    $assert->pageTextContains('The block configuration has been saved.');

    $this->drupalGet('<front>');
    // Verify page output.
    $assert->elementTextContains('css', '#block-feedblocktest h2', 'Feed Block Test');
    $assert->pageTextContains('Fri, 01/11/2019 - 06:26');
    $assert->linkByHrefExists('https://www.youtube.com/watch?v=ujn7w42ZZPY');
    $assert->linkByHrefExists('https://drupal.org');
  }

}
