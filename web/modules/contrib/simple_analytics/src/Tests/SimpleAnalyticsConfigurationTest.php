<?php

namespace Drupal\simple_analytics\Tests;

/**
 * Test basic pages of Simple Analytics module.
 *
 * - Tracking code on basic pages
 * - Help page for admin user.
 *
 * @group Simple Analytics
 */
class SimpleAnalyticsConfigurationTest extends SimpleAnalyticsTestBase {

  /**
   * Tests if configuration is possible.
   */
  public function testSimpleAnalyticsConfiguration() {
    // Login ad admin.
    $this->drupalLogin($this->admin_user);

    // Check if Configure links are available on menus.
    // Requires 'administer modules' permission.
    $this->drupalGet('admin/modules');
    $this->assertRaw('admin/config/system/simple-analytics', '[testSimpleAnalyticsConfiguration]: Configure link from Extend page to Simple Analytics Settings page exists.');
    $this->drupalGet('admin/config/system');
    $this->assertRaw('admin/config/system/simple-analytics', '[testSimpleAnalyticsConfiguration]: Configure link from System Reports to Simple Analytics Settings page exists.');

    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/simple-analytics');
    $this->assertRaw(t('Simple Analytics settings'), '[testSimpleAnalyticsConfiguration]: Settings page displayed.');

    // Check for Piwik account URL/ID validation..
    $edit['piwik-uri'] = 'http://www.domain.com/piwik';
    $this->drupalPostForm('admin/config/system/simple-analytics', $edit, t('Save configuration'));
    $this->assertRaw(t('If you set a piwik URL, You must also specify the Piwik ID.'), '[testSimpleAnalyticsConfiguration]: Invalid Web Property ID number validated.');
  }

}
