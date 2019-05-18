<?php

namespace Drupal\linkchecker\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test case for interface tests.
 *
 * @group Link checker
 */
class LinkCheckerInterfaceTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'linkchecker',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $full_html_format = filter_format_load('full_html');
    $permissions = [
      // Block permissions.
      'administer blocks',
      // Comment permissions.
      'administer comments',
      'access comments',
      'post comments',
      'skip comment approval',
      'edit own comments',
      // Node permissions.
      'create page content',
      'edit own page content',
      // Path aliase permissions.
      'administer url aliases',
      'create url aliases',
      // Content filter permissions.
      filter_permission_name($full_html_format),
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test the interface functionality.
   */
  public function testLinkCheckerCreateNodeWithBrokenLinks() {
    // Enable all node type page for link extraction.
    variable_set('linkchecker_scan_node_page', TRUE);

    // Core enables the URL filter for "Full HTML" by default.
    // -> Blacklist / Disable URL filter for testing.
    variable_set('linkchecker_filter_blacklist', array('filter_url' => 'filter_url'));

    // Extract from all link checker supported HTML tags.
    variable_set('linkchecker_extract_from_a', 1);
    variable_set('linkchecker_extract_from_audio', 1);
    variable_set('linkchecker_extract_from_embed', 1);
    variable_set('linkchecker_extract_from_iframe', 1);
    variable_set('linkchecker_extract_from_img', 1);
    variable_set('linkchecker_extract_from_object', 1);
    variable_set('linkchecker_extract_from_video', 1);

    $url1 = 'http://example.com/node/broken/link';
    $body = 'Lorem ipsum dolor sit amet <a href="' . $url1 . '">broken link</a> sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat';

    // Save folder names in variables for reuse.
    $folder1 = $this->randomName(10);
    $folder2 = $this->randomName(5);

    // Fill node array.
    $langcode = LANGUAGE_NONE;
    $edit = array();
    $edit['title'] = $this->randomName(32);
    $edit["body[$langcode][0][value]"] = $body;
    $edit['path[alias]'] = $folder1 . '/' . $folder2;
    $edit["body[$langcode][0][format]"] = 'full_html';

    // Extract only full qualified URLs.
    variable_set('linkchecker_check_links_types', 1);

    // Verify path input field appears on add "Basic page" form.
    $this->drupalGet('node/add/page');
    // Verify path input is present.
    $this->assertFieldByName('path[alias]', '', 'Path input field present on add Basic page form.');

    // Save node.
    $this->drupalPost('node/add/page', $edit, t('Save'));
    $this->assertText(t('@type @title has been created.', array('@type' => 'Basic page', '@title' => $edit['title'])), 'Node was created.');

    $node = $this->drupalGetNodeByTitle($edit['title']);
    $this->assertTrue($node, 'Node found in database.');

    // Verify if the content link is extracted properly.
    $link = $this->getLinkCheckerLink($url1);
    if ($link) {
      $this->assertIdentical($link->url, $url1, format_string('URL %url found.', array('%url' => $url1)));
    }
    else {
      $this->fail(format_string('URL %url not found.', array('%url' => $url1)));
    }

    // Set link as failed once.
    $fail_count = 1;
    $status = '301';
    $this->setLinkAsBroken($url1, $status, $fail_count);
    $this->drupalGet('node/' . $node->nid . '/edit');
    $this->assertRaw(\Drupal::translation()->formatPlural($fail_count, 'Link check of <a href="@url">@url</a> failed once (status code: @code).', 'Link check of <a href="@url">@url</a> failed @count times (status code: @code).', array('@url' => $url1, '@code' => $status)), 'Link check failed once found.');

    // Set link as failed multiple times.
    $fail_count = 4;
    $status = '404';
    $this->setLinkAsBroken($url1, $status, $fail_count);
    $this->drupalGet('node/' . $node->nid . '/edit');
    $this->assertRaw(\Drupal::translation()->formatPlural($fail_count, 'Link check of <a href="@url">@url</a> failed once (status code: @code).', 'Link check of <a href="@url">@url</a> failed @count times (status code: @code).', array('@url' => $url1, '@code' => $status)), 'Link check failed multiple times found.');
  }

  public function testLinkCheckerCreateBlockWithBrokenLinks() {
    // Enable all blocks for link extraction.
    variable_set('linkchecker_scan_blocks', 1);

    // Confirm that the add block link appears on block overview pages.
    $this->drupalGet('admin/structure/block');
    $this->assertRaw(l(t('Add block'), 'admin/structure/block/add'), 'Add block link is present on block overview page for default theme.');

    $url1 = 'http://example.com/block/broken/link';
    $body = 'Lorem ipsum dolor sit amet <a href="' . $url1 . '">broken link</a> sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat';

    // Add a new custom block by filling out the input form on the admin/structure/block/add page.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = $body;
    $custom_block['body[format]'] = 'full_html';
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    // Confirm that the custom block has been created, and then query the created bid.
    $this->assertText(t('The block has been created.'), 'Custom block successfully created.');
    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();

    // Check to see if the custom block was created by checking that it's in the database.
    $this->assertNotNull($bid, 'Custom block found in database');

    // Verify if the content link is extracted properly.
    $link = $this->getLinkCheckerLink($url1);
    if ($link) {
      $this->assertIdentical($link->url, $url1, format_string('URL %url found.', array('%url' => $url1)));
    }
    else {
      $this->fail(format_string('URL %url not found.', array('%url' => $url1)));
    }

    // Set link as failed once.
    $fail_count = 1;
    $status = '301';
    $this->setLinkAsBroken($url1, $status, $fail_count);
    $this->drupalGet('admin/structure/block/manage/block/' . $bid . '/configure');
    $this->assertRaw(\Drupal::translation()->formatPlural($fail_count, 'Link check of <a href="@url">@url</a> failed once (status code: @code).', 'Link check of <a href="@url">@url</a> failed @count times (status code: @code).', array('@url' => $url1, '@code' => $status)), 'Link check failed once found.');

    // Set link as failed multiple times.
    $fail_count = 4;
    $status = '404';
    $this->setLinkAsBroken($url1, $status, $fail_count);
    $this->drupalGet('admin/structure/block/manage/block/' . $bid . '/configure');
    $this->assertRaw(\Drupal::translation()->formatPlural($fail_count, 'Link check of <a href="@url">@url</a> failed once (status code: @code).', 'Link check of <a href="@url">@url</a> failed @count times (status code: @code).', array('@url' => $url1, '@code' => $status)), 'Link check failed multiple times found.');
  }

  /**
   * Set an URL as broken.
   *
   * @param string $url
   *   URL of the link to find.
   * @param string $status
   *   A fake HTTP code for testing.
   */
  function setLinkAsBroken($url = NULL, $status = '404', $fail_count = 0) {
    db_update('linkchecker_link')
    ->condition('urlhash', drupal_hash_base64($url))
    ->fields(array(
      'code' => $status,
      'error' => 'Not available (test running)',
      'fail_count' => $fail_count,
      'last_checked' => time(),
      'status' => 1,
    ))
    ->execute();
  }

  /**
   * Get linkchecker link by url.
   *
   * @param string $url
   *   URL of the link to find.
   *
   * @return object
   *   The link object.
   */
  function getLinkCheckerLink($url) {
    return db_query('SELECT * FROM {linkchecker_link} WHERE urlhash = :urlhash', array(':urlhash' => drupal_hash_base64($url)))->fetchObject();
  }
}
