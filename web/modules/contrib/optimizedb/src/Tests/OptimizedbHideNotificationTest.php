<?php

/**
 * @file
 * Tests for optimizedb module.
 */

namespace Drupal\optimizedb\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the page hide notification.
 *
 * @group optimizedb
 */
class OptimizedbHideNotificationTest extends WebTestBase {

  /**
   * Disabled config schema checking temporarily until all errors are resolved.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = ['optimizedb'];

  /**
   * A user with permission the settings module.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer optimizedb settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Display notification of the need to perform optimization.
   */
  public function testHideNotification() {
    $config = $this->config('optimizedb.settings');

    $config
      ->set('optimizedb_notify_optimize', FALSE)
      ->save();

    $this->drupalGet('admin/config/development/optimizedb/hide');
    $this->assertText(t('Alerts are not available.'));

    $config
      ->set('optimizedb_notify_optimize', TRUE)
      ->save();

    $this->drupalGet('admin/config/development/optimizedb/hide');
    $this->assertNoText(t('Alerts are not available.'));

    $notify_optimize = $this->config('optimizedb.settings')
      ->get('optimizedb_notify_optimize');
    $this->assertFalse($notify_optimize);
  }

}
