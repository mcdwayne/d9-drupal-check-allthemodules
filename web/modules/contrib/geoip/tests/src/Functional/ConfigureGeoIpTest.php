<?php

namespace Drupal\Tests\geoip\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the GeoIP configuration form.
 *
 * @group geoip
 */
class ConfigureGeoIpTest extends BrowserTestBase {

  public static $modules = [
    'geoip',
  ];

  /**
   * Tests the configure form.
   */
  public function testConfigureForm() {
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->drupalGet(Url::fromRoute('system.admin_config_system'));
    $this->assertSession()->linkExists('Configure GeoIP');
  }

}
