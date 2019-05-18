<?php

namespace Drupal\Tests\role_test_accounts\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests Role Test Accounts module install and uninstall hooks.
 *
 * @group role_test_accounts
 */
class RoleTestAccountsInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');

    $role = Role::create(['id' => 'editor']);
    $role->save();
  }

  /**
   * Tests Role Test Accounts module install and uninstall hooks.
   */
  public function testInstallUninstallHooks() {
    $this->assertFalse(user_load_by_name('test.editor'));

    \Drupal::service('module_installer')->install(['role_test_accounts']);
    $this->assertNotFalse(user_load_by_name('test.editor'));

    \Drupal::service('module_installer')->uninstall(['role_test_accounts']);
    $this->assertFalse(user_load_by_name('test.editor'));
  }

}
