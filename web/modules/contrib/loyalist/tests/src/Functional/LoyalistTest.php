<?php

namespace Drupal\Tests\loyalist\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the basic functions of the Loyalist module.
 *
 * @todo Add tests for session variables and event dispatching.
 *
 * @group Loyalist
 */
class LoyalistTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['system', 'block', 'loyalist'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add the system menu blocks to appropriate regions.
    $this->setupMenus();
  }

  /**
   * Set up menus and tasks in their regions.
   */
  protected function setupMenus() {
    $this->drupalPlaceBlock('system_menu_block:tools', ['region' => 'primary_menu']);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'secondary_menu']);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content']);
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
  }

  /**
   * Test administrative functions.
   */
  public function testModuleAdministration() {
    $assert = $this->assertSession();

    $web_user = $this->drupalCreateUser([
      'administer loyalist',
    ]);

    // Anonymous user should not have access.
    $this->drupalGet('/admin/config/people/loyalist');
    $assert->statusCodeEquals(403);

    $this->drupalLogin($web_user);

    // Web user should have access.
    $this->drupalGet('/admin/config/people/loyalist');
    $assert->statusCodeEquals(200);

    // Assert defaults.
    $assert->fieldValueEquals('interval', 604800);
    $assert->fieldValueEquals('visits', 3);
    $assert->fieldValueEquals('cooldown', 1800);

    // Post content, save an instance. Go back to list after saving.
    $edit = [
      'interval' => 2592000,
      'visits' => 10,
      'cooldown' => 86400,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    // Assert updated settings.
    $assert->fieldValueEquals('interval', 2592000);
    $assert->fieldValueEquals('visits', 10);
    $assert->fieldValueEquals('cooldown', 86400);
  }

}
