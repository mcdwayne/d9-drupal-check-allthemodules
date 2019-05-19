<?php

namespace Drupal\sitemap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the landing and admin pages of the sitemap.
 *
 * @group sitemap
 */
class SitemapTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sitemap'];

  /**
   * User accounts.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $userAdmin;
  public $userView;
  public $userNoAccess;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user with admin permissions.
    $this->userAdmin = $this->drupalCreateUser([
      'administer sitemap',
      'access sitemap',
    ]);

    // Create user with view permissions.
    $this->userView = $this->drupalCreateUser([
      'access sitemap',
    ]);

    // Create user without any sitemap permissions.
    $this->userNoAccess = $this->drupalCreateUser();
  }

}
