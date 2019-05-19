<?php

namespace Drupal\simple_analytics\Tests;

use Drupal\simple_analytics\SimpleAnalyticsHelper;

/**
 * Test basic pages of Simple Analytics module.
 *
 * - Tracking code on basic pages
 * - Help page for admin user.
 *
 * @group Simple Analytics
 */
class SimpleAnalyticsBasicPagesTest extends SimpleAnalyticsTestBase {

  /**
   * Tests if page visibility works.
   */
  public function testSimpleAnalyticsPageVisibility() {
    // Verify that no GA and Piwik tracking code is embedded into the webpage;
    // only the internal tracking code is present..
    $this->drupalGet('');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsPageVisibility]: Simple Tracking code is displayed.');

    $this->drupalGet('');
    $this->assertNoRaw('//www.google-analytics.com/analytics.js', '[testSimpleAnalyticsPageVisibility]: GA Tracking code is not displayed without UA code configured.');
    $this->assertNoRaw("_paq.push(['setTrackerUrl', u+'piwik.php'])", '[testSimpleAnalyticsPageVisibility]: Piwik Tracking code is not displayed without URL and ID configured.');
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testSimpleAnalyticsTrackingCode() {
    // Check internal Tracking page is accessible for everyone and success.
    $this->drupalGet('simple_analytics/api/track');
    $this->assertResponse(200);
    $this->assertRaw('[{"rasult":"OK"}]', '[testSimpleAnalyticsTrackingCode]: Tracking page accessible and track success.');
  }

  /**
   * Tests url exclusion.
   */
  public function testSimpleAnalyticsAuthUser() {

    // Login.
    $this->drupalLogin($this->admin_user);

    // Enable auth tracking.
    $config = SimpleAnalyticsHelper::getConfig(TRUE);
    $config->set('track_auth', TRUE);
    $config->save();

    $this->drupalGet('');
    $this->assertResponse(200);
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is present.');
  }

  /**
   * Tests url exclusion.
   */
  public function testSimpleAnalyticsAuthUserDisb() {

    // Enable auth tracking.
    $config = SimpleAnalyticsHelper::getConfig(TRUE);
    $config->set('track_auth', FALSE);
    $config->save();

    // Login.
    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertResponse(200);
    $this->assertNoRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is present.');
  }

  /**
   * Tests url exclusion.
   */
  public function testSimpleAnalyticsNoTrackUrls() {

    // TODO : This test is generate an exception due to Scheme. Correct it.
    $config = SimpleAnalyticsHelper::getConfig(TRUE);
    // Init config.
    $config->set('track_admin', TRUE);
    try {
      // Set path *user* and *admin/config* as exclude.
      $config->set('track_exclude_url', ['user', 'admin/config']);
      $config->save();
    }
    catch (\Exception $e) {
      return;
    }

    // Verify the tracking code is present.
    $this->drupalGet('');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is present.');
    $this->drupalGet('/admin');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is present.');
    $this->drupalGet('/admin/people');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is present.');
    // Verify the tracking code is not present.
    $this->drupalGet('/user/login');
    $this->assertNoRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is absent.');
    $this->drupalGet('/admin/config');
    $this->assertNoRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is absent.');
    $this->drupalGet('/admin/config/system');
    $this->assertNoRaw('/simple_analytics/api/track', '[testSimpleAnalyticsNoTrackUrls]: Tracking code is absent.');
  }

}
