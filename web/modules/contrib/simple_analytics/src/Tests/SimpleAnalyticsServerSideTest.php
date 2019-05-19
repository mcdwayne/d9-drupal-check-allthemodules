<?php

namespace Drupal\simple_analytics\Tests;

use Drupal\Core\Database\Database;
use Drupal\simple_analytics\SimpleAnalyticsHelper;

/**
 * Server side tracking test of Simple Analytics module.
 *
 * - Server side tracking test.
 * - Tracking code not preset on basic pages.
 *
 * @group Simple Analytics
 */
class SimpleAnalyticsServerSideTest extends SimpleAnalyticsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Activate server side tracking.
    $config = SimpleAnalyticsHelper::getConfig(TRUE);
    $config->set('sa_tracker_server', TRUE);
    $config->save();
  }

  /**
   * Tests if page visibility works.
   */
  public function testSimpleAnalyticsPageVisibility() {
    // Verify that no GA and Piwik tracking code is embedded into the webpage;
    // only the internal tracking code is present..
    $this->drupalGet('');
    $this->assertNoRaw('simple_analytics/api/track', '[testSimpleAnalyticsPageVisibility]: Simple Tracking code is not displayed.');
    $this->assertNoRaw('//www.google-analytics.com/analytics.js', '[testSimpleAnalyticsPageVisibility]: GA Tracking code is not displayed without UA code configured.');
    $this->assertNoRaw("_paq.push(['setTrackerUrl', u+'piwik.php'])", '[testSimpleAnalyticsPageVisibility]: Piwik Tracking code is not displayed without URL and ID configured.');
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testSimpleAnalyticsTrackingTest() {

    // Login ad admin.
    $this->drupalLogin($this->admin_user);

    $con = Database::getConnection();
    $table_data = 'simple_analytics_data';

    // Count initial number of rows.
    $result = $con->select($table_data, 't')->fields('t', ['id'])->execute()->fetchAll();
    $count = count($result);
    $this->assertEqual($count, 3, "Data count : $count is OK");

    // Must increment.
    $this->drupalGet('');
    $this->assertResponse(200);
    $result = $con->select($table_data, 't')->fields('t', ['id'])->execute()->fetchAll();
    $count = count($result);
    $this->assertEqual($count, 5, "Data count : $count is OK");

    // Must not increment.
    $this->drupalGet('/simple_analytics/api/live');
    $this->assertResponse(200);
    $result = $con->select($table_data, 't')->fields('t', ['id'])->execute()->fetchAll();
    $count = count($result);
    $this->assertEqual($count, 5, "Data count : $count is OK");
  }

}
