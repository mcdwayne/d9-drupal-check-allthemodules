<?php

namespace Drupal\Tests\one_time_password\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Test installation and uninstallation of the module.
 *
 * @group one_time_password
 */
class InstallationTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'one_time_password',
  ];

  /**
   * Test the installation and uninstallation.
   */
  public function testInstallUninstall() {
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    $this->assertArrayHasKey('one_time_password', $installed_modules, 'The module was installed successfully.');

    // Create some field data.
    $user = User::load(1);
    $user->one_time_password->regenerateOneTimePassword();
    $user->save();

    $this->container->get('module_installer')->uninstall(['one_time_password']);
    $this->rebuildContainer();

    $installed_modules = $this->container->get('module_handler')->getModuleList();
    $this->assertArrayNotHasKey('one_time_password', $installed_modules, 'The module was uninstalled successfully.');

    $pending_entity_updates = $this->container->get('entity.definition_update_manager')->getChangeSummary();
    $this->assertEmpty($pending_entity_updates);
  }

}
