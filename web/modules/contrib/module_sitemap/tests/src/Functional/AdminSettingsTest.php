<?php

namespace Drupal\module_sitemap\Tests;

use Drupal\Tests\module_sitemap\Functional\FunctionalTestBase;

/**
 * Tests configuration for the admin settings form.
 *
 * @group module_sitemap
 */
class AdminSettingsTest extends FunctionalTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['module_sitemap'];

  /**
   * Test the configuration to make sure the variables get installed.
   */
  public function testSettings() {
    $config = $this->config('module_sitemap.settings');
    $this->assertNotNull($config->get('display_full_url'), '"display_full_url" variable exists.');
    $this->assertNotNull($config->get('group_by_module'), '"group_by_module" variable exists.');
  }

}
