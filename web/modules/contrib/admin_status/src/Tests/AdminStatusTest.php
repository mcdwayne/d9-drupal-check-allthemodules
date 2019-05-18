<?php

namespace Drupal\admin_status\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the functionality of the Admin Status module.
 *
 * @group admin_status
 */
class AdminStatusTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['admin_status'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Tests the loading of the plugin manager and that plugins are registered.
   */
  public function testAdminStatusPlugin() {
    $manager = \Drupal::service('plugin.manager.admin_status');

    $admin_status_plugin_definitions = $manager->getDefinitions();

    // Check we have only one admin_status plugin.
    $this->assertEqual(count($admin_status_plugin_definitions), 1, 'There is one admin_status plugin defined.');

    // Check some of the properties of the admin_status plugin definition.
    $admin_status_plugin_definition = $admin_status_plugin_definitions['default_message'];
    $this->assertEqual($admin_status_plugin_definition['name'], 'Default Message', "The default message admin_status plugin definition's name property is set.");

    // Create an instance of the default message admin_status plugin to check it
    // works.
    $plugin = $manager->createInstance('default_message', ['of' => 'configuration values']);

    $this->assertEqual(get_class($plugin), 'Drupal\admin_status\Plugin\AdminStatus\DefaultMsg', 'The default message admin_status plugin is instantiated and of the correct class.');
  }

  /**
   * Test the output of the config form page.
   */
  public function testAdminStatusConfigPage() {
    $this->drupalGet('admin/config/system/admin_status');
    $this->assertResponse(200, 'Admin Status config form page successfully accessed.');

    // Check we see the plugin id.
    $this->assertText(t('Default Message'), 'The plugin name is output.');

    // Check we see the plugin description.
    $this->assertText(t('Display a generic message.'), 'The plugin description is output.');
  }

}
