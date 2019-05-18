<?php

namespace Drupal\Tests\onlyone\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the module configurations.
 *
 * @group onlyone
 */
class DefaultConfigurationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['onlyone'];

  /**
   * Tests the default configuration values.
   */
  public function testDefaultConfigurationValues() {
    // Installing the configuration file.
    $this->installConfig(self::$modules);
    // Getting the config file.
    $config_file = $this->container->get('config.factory')->get('onlyone.settings');
    // Checking if the onlyone_node_types variable is empty.
    $this->assertEmpty($config_file->get('onlyone_node_types'), t('The default configuration value for onlyone_node_types should be empty.'));
    // Checking if the onlyone_new_menu_entry variable is FALSE.
    $this->assertFalse($config_file->get('onlyone_new_menu_entry'), 'The default configuration value for onlyone_new_menu_entry should be FALSE.');
  }

}
