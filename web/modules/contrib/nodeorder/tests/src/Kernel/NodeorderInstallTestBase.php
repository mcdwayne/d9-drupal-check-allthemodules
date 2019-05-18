<?php

namespace Drupal\Tests\nodeorder\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for module installation and uninstallation tests.
 */
abstract class NodeorderInstallTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter', 'text', 'taxonomy'];

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstaller
   */
  protected $moduleInstaller;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->moduleInstaller = $this->container->get('module_installer');
    $this->database = $this->container->get('database');

    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['filter', 'taxonomy']);

    // Manually load and enable/install module.
    // This allows to call hook_install() properly.
    module_load_install('nodeorder');
    $this->moduleInstaller->install(['nodeorder']);
  }

}
