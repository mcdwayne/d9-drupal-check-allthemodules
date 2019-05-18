<?php

namespace Drupal\Tests\google_appliance\Functional;

/**
 * Tests Google appliance administration form.
 *
 * @group google_appliance
 */
class AdministrationFormTest extends GoogleApplianceFunctionalTestBase {

  /**
   * Tests admin form.
   */
  public function testAdminForm() {
    $this->drupalGet('/admin/config/search/google_appliance/settings');
    $assert = $this->assertSession();
    // Check non-admins cannot access page.
    $assert->statusCodeEquals(403);

    // Now login.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/search/google_appliance/settings');
    $assert->statusCodeEquals(200);
    $this->submitForm([
      'hostname' => 'http://www.mygsa.net',
      'collection' => 'default_collection',
      'frontend' => 'default_frontend',
      'timeout' => 10,
      'autofilter' => '1',
      'query_inspection' => FALSE,
      'search_title' => $this->randomString(),
      'results_per_page' => 16,
    ], 'Save configuration');

    $config = $this->container->get('config.factory')->get('google_appliance.settings');
    $this->assertEquals('http://www.mygsa.net', $config->get('connection_info.hostname'));
    $this->assertEquals(16, $config->get('display_settings.results_per_page'));
  }

}
