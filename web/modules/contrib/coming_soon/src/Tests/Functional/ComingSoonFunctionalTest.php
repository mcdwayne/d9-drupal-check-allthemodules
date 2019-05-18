<?php

namespace Drupal\coming_soon\Tests\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Coming Soon interactions are working as expected.
 *
 * @group coming_soon
 */
class ComingSoonFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'coming_soon'];

  /**
   * A simple user with 'access content' permission.
   *
   * @var object
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer coming soon']);
  }

  /**
   * Tests that the 'admin/config/coming_soon' path returns the right content.
   */
  public function testAdminPageAccessible() {
    $this->drupalLogin($this->user);
    /*
    Test the page is found & accessible for users with "administer coming soon"
    permission.
     */
    $this->drupalGet('admin/config/coming_soon');
    $this->assertSession()->statusCodeEquals(200);
  }

}
