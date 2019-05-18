<?php

namespace Drupal\Tests\drupal_content_sync\Functional;

/**
 * Tests the settings page.
 *
 * @group dcs
 */
class SettingsPage extends TestBase {

  /**
   *
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Ensure the pool overview page is reachable.
   */
  public function testSettingsPage() {
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/config/services/drupal_content_sync/settings');

    // Test that the settings page is reachable.
    $this->assertSession()->statusCodeEquals(200);

    // Test that the Base URL can be set.
    $test_dcs_base_url = 'http://dcs-base-url.com';
    $page->fillField('Base URL', $test_dcs_base_url);
    $page->pressButton('Save configuration');
    $this->drupalGet('admin/config/services/drupal_content_sync/settings');
    $this->assertSession()->fieldValueEquals('edit-dcs-base-url', $test_dcs_base_url);
  }

}
