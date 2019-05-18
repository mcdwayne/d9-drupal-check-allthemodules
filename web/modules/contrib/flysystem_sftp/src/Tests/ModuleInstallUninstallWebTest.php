<?php

/**
 * @file
 * Contains \Drupal\flysystem_sftp\Tests\ModuleInstallUninstallWebTest.
 */

namespace Drupal\flysystem_sftp\Tests;

use Drupal\flysystem\Tests\ModuleInstallUninstallWebTest as Base;

/**
 * Tests module installation and uninstallation.
 *
 * @group flysystem_sftp
 */
class ModuleInstallUninstallWebTest extends Base {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['flysystem_sftp'];

}
