<?php

namespace Drupal\Tests\onlyone\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the onlyone_admin_settings configuration form.
 *
 * @group onlyone
 */
class OnlyOneAdminSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['onlyone'];

  /**
   * Tests the configuration form, the permission and the link.
   */
  public function testConfigurationForm() {
    // Going to the config page.
    $this->drupalGet('/admin/config/content/onlyone/settings');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(['administer onlyone', 'access administration pages']);
    // Log in.
    $this->drupalLogin($account);

    // Checking the module link.
    $this->drupalGet('/admin/config/content');
    $this->assertSession()->linkByHrefExists('/admin/config/content/onlyone');

    // Going to the config page.
    $this->drupalGet('/admin/config/content/onlyone/settings');
    // Checking that the request has succeeded.
    $this->assertSession()->statusCodeEquals(200);

    // Checking the page title.
    $this->assertSession()->elementTextContains('css', 'h1', 'Only One Settings');
    // Check that the checkbox is unchecked.
    $this->assertSession()->checkboxNotChecked('onlyone_new_menu_entry');

    // Form values to send (checking check checkbox).
    $edit = [
      'onlyone_new_menu_entry' => 1,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');
    // Verifiying the save message.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');

    // Getting variables.
    $onlyone_new_menu_entry = $config_factory->get('onlyone.settings')->get('onlyone_new_menu_entry');

    // Verifiying that the config values are stored.
    $this->assertTrue($onlyone_new_menu_entry, 'The configuration value for onlyone_new_menu_entry should be TRUE.');

    // Form values to send (checking uncheck checkbox).
    $edit = [
      'onlyone_new_menu_entry' => 0,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');

    // Getting variables.
    $onlyone_new_menu_entry = $config_factory->get('onlyone.settings')->get('onlyone_new_menu_entry');
    // Verifiying that the config values are stored.
    $this->assertFalse($onlyone_new_menu_entry, 'The configuration value for onlyone_new_menu_entry should be FALSE.');
  }

}
