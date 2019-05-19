<?php

namespace Drupal\Tests\system_status\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings.
 *
 * @group system_status
 */
class SettingsTest extends BrowserTestBase {

  public static $modules = ['system_status'];

  /**
   * Tests the settings form.
   */
  public function testSettings() {
    // Make sure we can't access settings without permissions.
    $this->drupalGet('/admin/config/system/system-status');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer site configuration']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/system/system-status');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure api keys gets populated.
    $key = $this->getSession()->getPage()->find('css', 'input[name="system_status_service"]');
    $this->assertNotEmpty($key);
  }

}
