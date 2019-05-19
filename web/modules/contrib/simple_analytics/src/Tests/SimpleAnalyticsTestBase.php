<?php

namespace Drupal\simple_analytics\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\simple_analytics\SimpleAnalyticsHelper;

/**
 * Test basic functionality of Simple Analytics module.
 *
 * @group Simple Analytics
 */
class SimpleAnalyticsTestBase extends WebTestBase {

  /**
   * User with admin Simple Analytics permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  const SA_PIWIK_DOMAIN = "www.domain.com/piwik";
  const SA_PIWIK_ID = 123;
  const SA_GA_ID = "UA-123456-1";

  /**
   * User with admin Simple Analytics permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userModerator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'simple_analytics',
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add Simple Analytics view permissions.
    $permissions = [
      'access administration pages',
      'simple_analytics view_history',
      'simple_analytics view_details',
    ];

    // User to set up simple_analytics.
    $this->userModerator = $this->drupalCreateUser($permissions);

    // Add Simple Analytics admin permissions.
    $permissions[] = 'simple_analytics admin';
    $this->admin_user = $this->drupalCreateUser($permissions, 'SimpleAnalyticsAdmin', TRUE);

    // Set default settings.
    $config = SimpleAnalyticsHelper::getConfig(TRUE);
    // Mode JS.
    $config->set('sa_tracker_server', FALSE);
    $config->save();

  }

  /**
   * Tests if page visibility works.
   */
  public function testSimpleAnalyticsPageVisibility() {
    // Verify that no GA and Piwik tracking code is embedded into the webpage;
    // only the internal tracking code is present..
    $this->drupalGet('');
    $this->assertRaw('/simple_analytics/api/track', '[testSimpleAnalyticsAdminPageVisibility]: Simple Tracking code is displayed.');

    $this->drupalGet('');
    $this->assertNoRaw('//www.google-analytics.com/analytics.js', '[testSimpleAnalyticsAdminPageVisibility]: GA Tracking code is not displayed without UA code configured.');
    $this->assertNoRaw("_paq.push(['setTrackerUrl', u+'piwik.php'])", '[testSimpleAnalyticsAdminPageVisibility]: Piwik Tracking code is not displayed without URL and ID configured.');
  }

}
