<?php

namespace Drupal\Tests\default_content_extra\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings page.
 *
 * @package Drupal\Tests\default_content_extra\Functional
 *
 * @group default_content_extra
 */
class DefaultContentExtraTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'default_content_extra',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
    ];

    $web_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($web_user);
  }

  /**
   * Test the settings page.
   */
  public function testSettingsPage() {
    // Load settings page.
    $settings = 'admin/config/content/default-content-extra';
    $this->drupalGet($settings);
    $this->assertSession()->statusCodeEquals(200);

    // Check path aliases.
    $this->assertSession()->pageTextContains('Path aliases');
    $this->assertSession()->checkboxChecked('edit-path-alias');

    // Delete users.
    $this->assertSession()->pageTextContains('Delete users 0 and 1');
    $this->assertSession()->checkboxChecked('edit-delete-users');

    // Save settings form.
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
