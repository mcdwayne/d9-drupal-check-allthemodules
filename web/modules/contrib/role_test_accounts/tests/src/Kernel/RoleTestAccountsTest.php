<?php

namespace Drupal\Tests\role_test_accounts\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

/**
 * Tests the Role Test Accounts module.
 *
 * @group role_test_accounts
 */
class RoleTestAccountsConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'role_test_accounts'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installConfig('role_test_accounts');
  }

  /**
   * Tests user accounts are created on role insert and deleted on role delete.
   */
  public function testAccountCreateAndDelete() {
    $role = Role::create(['id' => 'test_role']);
    $role->save();
    $this->assertNotFalse(user_load_by_name('test.test_role'));

    $role->delete();
    $this->assertFalse(user_load_by_name('test.test_role'));
  }

  /**
   * Tests accounts are only created for configured roles.
   */
  public function testTestRoleConfiguration() {
    $role = Role::create(['id' => 'admin']);
    $role->save();
    $this->assertNotFalse(user_load_by_name('test.admin'));

    // Update configuration: admin only.
    $config = \Drupal::configFactory()->getEditable('role_test_accounts.settings');
    $config
      ->set('selection_method', 'include')
      ->set('selected_roles', ['admin'])
      ->save();

    $role = Role::create(['id' => 'editor']);
    $role->save();
    $this->assertFalse(user_load_by_name('test.editor'));

    // Update configuration: not admin.
    $config
      ->set('selection_method', 'exclude')
      ->set('selected_roles', ['admin'])
      ->save();
    $this->assertFalse(user_load_by_name('test.admin'));
    $this->assertNotFalse(user_load_by_name('test.editor'));
  }

  /**
   * Tests the password configuration updates the role test accounts.
   */
  public function testPasswordConfiguration() {
    $role = Role::create(['id' => 'editor']);
    $role->save();

    $new_password = 'test';
    $config = \Drupal::configFactory()->getEditable('role_test_accounts.settings');
    $config->set('password', $new_password)->save();

    $user = user_load_by_name('test.editor');
    $this->assertTrue($user instanceof UserInterface);
    $this->assertNotFalse(\Drupal::service('user.auth')->authenticate('test.editor', $new_password));
  }

}
