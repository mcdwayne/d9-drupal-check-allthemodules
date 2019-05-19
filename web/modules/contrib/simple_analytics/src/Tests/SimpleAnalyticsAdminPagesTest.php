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
class SimpleAnalyticsAdminPagesTest extends SimpleAnalyticsTestBase {

  /**
   * Tests if help sections are shown.
   */
  public function testSimpleAnalyticsHelp() {
    // Requires help.module.
    // Login as admin.
    $this->drupalLogin($this->admin_user);

    // Check help page.
    $this->drupalGet('admin/help/simple_analytics');
    $this->assertText('Simple analytics allow to integrate a site analytics code easily', '[testSimpleAnalyticsHelp]: Simple Analytics help text shown in help section.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testSimpleAnalyticsAdminPageVisibility() {
    // Login ad admin.
    $this->drupalLogin($this->admin_user);

    // GA and Piwik configuration.
    $config = $this->config('simple_analytics.settings');
    $config->set('google-id', self::SA_GA_ID);
    $config->set('piwik-uri', 'http://' . self::SA_PIWIK_DOMAIN);
    $config->set('piwik-id', self::SA_PIWIK_ID);
    $config->set('track_admin', TRUE);
    $config->set('track_auth', TRUE);
    $config->set('sa_tracker_server', FALSE);
    $config->save();

    // Verify that no GA and Piwik tracking code is embedded into the webpage;
    // Check tracking code visibility on admin pages.
    $this->drupalGet('admin');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsAdminPageVisibility]: Simple Tracking code is displayed on admin pages.');
    $this->assertRaw('www.google-analytics.com/analytics.js', '[testSimpleAnalyticsAdminPageVisibility]: Google Tracking code is displayed.');
    $this->assertRaw(self::SA_PIWIK_DOMAIN, '[testSimpleAnalyticsAdminPageVisibility]: Piwik Tracking code is displayed.');

    // Recheck code on home.
    $this->drupalGet('');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsAdminPageVisibility]: Simple Tracking code is displayed.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertResponse(404);
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsPageVisibility]: Tracking code shown on non-existent page.');
  }

}
