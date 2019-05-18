<?php

namespace Drupal\Tests\degov_simplenews\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class InstallationTest.
 *
 * @package Drupal\Tests\degov_simplenews\Kernel
 */
class InstallationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['degov_simplenews'];

  /**
   * Tests that the module can be installed.
   */
  public function testInstallation(): void {
    $moduleInstaller = \Drupal::service('module_handler');
    self::assertTrue($moduleInstaller->moduleExists('degov_simplenews'));
  }

}
