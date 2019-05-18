<?php

namespace Drupal\Tests\search_api_saved_searches\Functional;

use Drupal\search_api_saved_searches\Entity\SavedSearchAccessControlHandler;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that this module provides correct cache metadata.
 *
 * @group search_api_saved_searches
 */
class CacheabilityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    // @todo Remove "rest" dependency once we depend on Search API 1.8. See
    //   #2953267.
    'rest',
    'search_api_saved_searches',
    'search_api_test_views',
  ];

  /**
   * The admin user used in this test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $permissions = [
      SavedSearchAccessControlHandler::ADMIN_PERMISSION,
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests caching of the "Save search" block.
   */
  public function testBlockCaching() {
    $block_label = 'Save search test block label';
    $this->drupalPlaceBlock('search_api_saved_searches', [
      'label' => $block_label,
      'type' => 'default',
    ]);

    // Visit a search page. Assert that the block is visible.
    $this->drupalGet('search-api-test');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains($block_label);

    // Now visit a page without a search. The block should not be visible.
    $this->drupalGet('user/1');
    $assert_session->pageTextNotContains($block_label);

    // If we set caching metadata correctly, visiting the search page again
    // should, again, give us the block.
    $this->drupalGet('search-api-test');
    $assert_session->pageTextContains($block_label);
  }

}
