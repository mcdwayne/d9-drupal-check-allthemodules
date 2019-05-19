<?php

namespace Drupal\Tests\sidr\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the module configurations.
 *
 * @group sidr
 */
class DefaultConfigurationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['sidr'];

  /**
   * Tests the default configuration values.
   */
  public function testDefaultConfigurationValues() {
    // Installing the configuration file.
    $this->installConfig(self::$modules);
    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');
    // Getting variable sidr_theme.
    $sidr_theme = $config_factory->get('sidr.settings')->get('sidr_theme');
    // Checking if the sidr_theme variable value is dark.
    $this->assertSame($sidr_theme, 'dark');
  }

}
