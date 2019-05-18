<?php

namespace Drupal\dblog_pager\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Basic test class for DBLog Pager.
 *
 * @group dblog_pager
 */
class DblogPagerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog_pager'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration', 'access site reports']);
  }

  /**
   * Tests that the main admin path returns correct contents.
   */
  public function testAdminPath() {
    $this->drupalLogin($this->user);
    $this->drupalGet(Url::fromRoute('system.logging_settings'));
    $this->assertResponse(200);
    $this->assertText('Show First/Last links');
  }

  /**
   * Test that a log entry loads and provides the correct navigation options.
   */
  public function testLogEntryLoad() {
    // View the database log report (to generate access denied event).
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(403);

    // Generate a 2nd log event.
    $this->drupalGet('admin/reports/dblog');

    // Login user and verify correct links on event details page.
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/reports/dblog/event/1');
    $this->assertResponse(200);
    $this->assertLink('Next');
    $this->assertNoLink('Previous');
    $this->assertNoLink('First');
    $this->assertLink('Last');
    $this->drupalGet('admin/reports/dblog/event/2');
    $this->assertResponse(200);
    $this->assertLink('Previous');
    $this->assertLink('First');
  }

}
