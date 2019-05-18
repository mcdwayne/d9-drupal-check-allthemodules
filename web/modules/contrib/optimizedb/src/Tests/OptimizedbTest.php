<?php

/**
 * @file
 * Tests for optimizedb module.
 */

namespace Drupal\optimizedb\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the module functions.
 *
 * @group optimizedb
 */
class OptimizedbTest extends WebTestBase {

  /**
   * Disabled config schema checking temporarily until all errors are resolved.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = ['optimizedb', 'locale'];

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
   * Sizes tables.
   */
  public function testTablesList() {
    $this->config('optimizedb.settings')
      ->set('tables_size', 0)
      ->save();

    // Function for output all database tables and update their total size.
    _optimizedb_tables_list();

    $this->assertNotEqual($this->config('optimizedb.settings')->get('tables_size'), 0);
  }

  /**
   * Testing module admin page buttons.
   */
  public function testButtonsExecutingCommands() {
    $this->drupalPostForm('admin/config/development/optimizedb', [], t('Optimize tables'));
    $this->assertText(t('The operation completed successfully.'));
  }

  /**
   * Test notify optimize in optimizedb_cron() function.
   */
  public function testCronNotifyOptimize() {
    $config = $this->config('optimizedb.settings');

    $config
      ->set('optimizedb_optimization_period', 1)
      ->set('optimizedb_last_optimization', REQUEST_TIME - ((3600 * 24) * 2))
      ->set('optimizedb_notify_optimize', FALSE)
      ->save();

    $this->cronRun();
    $this->assertTrue($this->config('optimizedb.settings')->get('optimizedb_notify_optimize'));
  }

}
