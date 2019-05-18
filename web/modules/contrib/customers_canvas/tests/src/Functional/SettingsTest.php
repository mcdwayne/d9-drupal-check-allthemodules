<?php

namespace Drupal\Tests\customers_canvas\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Settings Test confirms the settings page is functioning properly.
 *
 * @group customers_canvas
 */
class SettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['customers_canvas'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Set up the test here.
  }

  /**
   * Test callback.
   */
  public function testSettingsPage() {
    $admin_user = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/services/customers_canvas');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Customers Canvas Settings"]');
  }

}
