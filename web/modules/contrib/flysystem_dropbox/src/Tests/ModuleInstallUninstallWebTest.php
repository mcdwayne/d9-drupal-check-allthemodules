<?php

/**
 * @file
 * Contains \Drupal\flysystem_dropbox\Tests\ModuleInstallUninstallWebTest.
 */

namespace Drupal\flysystem_dropbox\Tests;

use Drupal\flysystem\Tests\ModuleInstallUninstallWebTest as Base;

/**
 * Tests module installation and uninstallation.
 *
 * @group flysystem_dropbox
 */
class ModuleInstallUninstallWebTest extends Base {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['flysystem_dropbox'];

}
