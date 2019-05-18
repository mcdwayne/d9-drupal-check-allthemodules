<?php

namespace Drupal\piwik\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test search functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikSearchTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik', 'search', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $permissions = [
      'access administration pages',
      'administer piwik',
      'search content',
      'create page content',
      'edit own page content',
    ];

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if search tracking is properly added to the page.
   */
  public function testPiwikSearchTracking() {
    $site_id = '1';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();
    $this->config('piwik.settings')->set('url_http', 'http://www.example.com/piwik/')->save();
    $this->config('piwik.settings')->set('url_https', 'https://www.example.com/piwik/')->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($site_id, '[testPiwikSearch]: Tracking code is displayed for authenticated users.');

    $this->drupalGet('search/node');
    $this->assertNoRaw('_paq.push(["trackSiteSearch", ', '[testPiwikSearch]: Search tracker not added to page.');

    // Enable site search support.
    $this->config('piwik.settings')->set('track.site_search', 1)->save();

    // Search for random string.
    $search = [];
    $search['keys'] = $this->randomMachineName(8);

    // Create a node to search for.
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = 'This is a test title';
    $edit['body[0][value]'] = 'This test content contains ' . $search['keys'] . ' string.';

    // Fire a search, it's expected to get 0 results.
    $this->drupalPostForm('search/node', $search, t('Search'));
    $this->assertRaw('_paq.push(["trackSiteSearch", ', '[testPiwikSearch]: Search results tracker is displayed.');
    $this->assertRaw('window.piwik_search_results = 0;', '[testPiwikSearch]: Search yielded no results.');

    // Save the node.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertText(t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]), 'Basic page created.');

    // Index the node or it cannot found.
    $this->cronRun();

    $this->drupalPostForm('search/node', $search, t('Search'));
    $this->assertRaw('_paq.push(["trackSiteSearch", ', '[testPiwikSearch]: Search results tracker is displayed.');
    $this->assertRaw('window.piwik_search_results = 1;', '[testPiwikSearch]: One search result found.');

    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertText(t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]), 'Basic page created.');

    // Index the node or it cannot found.
    $this->cronRun();

    $this->drupalPostForm('search/node', $search, t('Search'));
    $this->assertRaw('_paq.push(["trackSiteSearch", ', '[testPiwikSearch]: Search results tracker is displayed.');
    $this->assertRaw('window.piwik_search_results = 2;', '[testPiwikSearch]: Two search results found.');
  }

}
