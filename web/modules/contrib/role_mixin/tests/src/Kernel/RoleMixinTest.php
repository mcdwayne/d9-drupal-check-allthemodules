<?php

namespace Drupal\Tests\role_mixin\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the general functionality of the role_mixin module.
 *
 * @group role_mixin
 */
class RoleMixinTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Fix the config schema in config/schema/role_mixin.schema.yml
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'role_mixin', 'system'];

  public function testRoleMixin() {
    $main_role1 = Role::create(['id' => 'main_role1']);
    $main_role1->save();
    $main_role2 = Role::create(['id' => 'main_role2']);
    $main_role2->save();

    $sub_role = Role::create([
      'id' => 'sub1',
    ]);
    $sub_role->grantPermission('perm1');
    $sub_role->grantPermission('perm2');
    $sub_role->setThirdPartySetting('role_mixin', 'parent_roles', ['main_role1', 'main_role2']);
    $sub_role->save();

    $main_role1 = Role::load('main_role1');
    $main_role2 = Role::load('main_role2');
    $this->assertTrue($main_role1->hasPermission('perm1'));
    $this->assertTrue($main_role1->hasPermission('perm2'));
    $this->assertFalse($main_role1->hasPermission('perm3'));

    $this->assertTrue($main_role2->hasPermission('perm1'));
    $this->assertTrue($main_role2->hasPermission('perm2'));
    $this->assertFalse($main_role2->hasPermission('perm3'));
  }

}
