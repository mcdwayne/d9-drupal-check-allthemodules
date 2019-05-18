<?php

namespace Drupal\Tests\google_analytics_counter\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the google analytics counter authentication settings form.
 *
 * @group google_analytics_counter
 */
class GoogleAnalyticsCounterAuthSettingsTest extends BrowserTestBase {
  const ADMIN_AUTH_SETTINGS_PATH = 'admin/config/system/google-analytics-counter/authentication';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Verifies that the google analytics counter settings page works.
   *
   * @see MediaSourceTest
   */
  public function testAuthSettings() {
    $this->container->get('module_installer')->install(['google_analytics_counter']);
    $this->resetAll();

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer google analytics counter',
    ));
    $this->drupalLogin($admin_user);

    // Create item(s) in the queue.
    $queue_name = 'google_analytics_counter_worker';
    $queue = \Drupal::queue($queue_name);

    // Enqueue an item for processing.
    $queue->createItem([$this->randomMachineName() => $this->randomMachineName()]);

    $assert = $this->assertSession();
    $this->drupalGet(self::ADMIN_AUTH_SETTINGS_PATH);
    $assert->statusCodeEquals(200);

    $edit = [];

    // Post form. Assert response.
    $this->submitForm($edit, t('Save configuration'));
  }

}
