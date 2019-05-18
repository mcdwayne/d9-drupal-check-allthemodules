<?php

namespace Drupal\Tests\role_mixin\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\role_mixin\MixinRolePermissions;
use Drupal\user\Entity\Role;

/**
 * @group role_mixin
 */
class MixinRolePermissionsTest extends \PHPUnit_Framework_TestCase {

  public function testGetPermissionsOfParentRoleWithOneMixinRole() {
    $etm = $this->prophesize(EntityTypeManagerInterface::class);
    $role_storage = $this->prophesize(EntityStorageInterface::class);
    $etm->getStorage('user_role')->willReturn($role_storage->reveal());

    $parent_role = $this->prophesize(Role::class);
    $mixin_role = $this->prophesize(Role::class);
    $mixin_role->getPermissions()->willReturn(['a', 'b', 'c']);
    $mixin_role->getThirdPartySetting('role_mixin', 'parent_roles')
      ->willReturn(['parent1']);

    $role_storage->loadMultiple()
      ->willReturn([
        'parent1' => $parent_role->reveal(),
        'mixin_role1' => $mixin_role->reveal()
      ]);

    $mixin_role_permissions = new MixinRolePermissions($etm->reveal());
    $this->assertEmpty($mixin_role_permissions->getPermissionsOfParentRole('mixin_role1'));
    $this->assertEquals(['a', 'b', 'c'], $mixin_role_permissions->getPermissionsOfParentRole('parent1'));
  }

}
