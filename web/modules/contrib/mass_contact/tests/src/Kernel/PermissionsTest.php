<?php

namespace Drupal\Tests\mass_contact\Kernel;

use Drupal\mass_contact\Permissions;

/**
 * Tests the dynamic permissions handler.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\Permissions
 */
class PermissionsTest extends MassContactTestBase {

  use CategoryCreationTrait;

  /**
   * Tests the dynamic permission handler.
   *
   * @covers ::categoryPermissions
   */
  public function testPermissions() {
    // Empty with no categories.
    $permissions = new Permissions();
    $this->assertEmpty($permissions->categoryPermissions());

    // Add a few categories.
    /** @var \Drupal\mass_contact\Entity\MassContactCategoryInterface[] $categories */
    $categories = [];
    foreach (range(1, 4) as $i) {
      $categories[$i] = $this->createCategory();
    }

    $permissions = $permissions->categoryPermissions();
    $this->assertEquals(4, count($permissions));
    $machine_names = array_keys($permissions);
    foreach ($categories as $category) {
      $permission = array_shift($machine_names);
      $this->assertEquals("mass contact send to users in the {$category->id()} category", $permission);
    }
  }

}
