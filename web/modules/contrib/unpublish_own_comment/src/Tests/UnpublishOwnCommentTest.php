<?php

/**
 * @file
 * Test case for testing the page_example module.
 *
 * This file contains the test cases to check if module is performing as
 * expected.
 */

namespace Drupal\unpublish_own_comment\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Creates page and render the content based on the arguments passed in thE URL.
 *
 * @group page_example
 * @group examples
 */
class UnpublishOwnCommentTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('unpublish_own_comment');

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * User object for our test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * Verify that current user has no access to page.
   *
   * @param string $url
   *   URL to verify.
   */
  public function pageExampleVerifyNoAccess($url) {
    // Test that page returns 403 Access Denied.
    $this->drupalGet($url);
    $this->assertResponse(403);
  }

  /**
   * Main test.
   *
   * Anonimous user can not access to unpublish action
   */
  public function testUnpublishOwnComment() {
    // Verify that anonymous user can't access the pages created by
    // page_example module.
    $this->pageExampleVerifyNoAccess('node/1/unpublish_own_comment/1');
  }

}
