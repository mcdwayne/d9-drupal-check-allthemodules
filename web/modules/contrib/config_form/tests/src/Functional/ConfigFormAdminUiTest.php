<?php

namespace Drupal\Tests\config_form\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test Configuration form functionality.
 *
 * @group config_form
 */
class ConfigFormAdminUiTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'node',
    'config_form',
  ];

  /**
   * An administrative user with permission to view configuration form.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test users.
    $this->adminUser = $this->drupalCreateUser([
      'administer configuration form',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test tab label changes.
   */
  public function testOverviewPage() {
    $this->drupalGet('/admin/config/forms');
    $this->assertSession()->pageTextContains('Configuration forms');
  }

}
