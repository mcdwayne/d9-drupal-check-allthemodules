<?php

namespace Drupal\Tests\user_badges\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user_badges\Entity\Badge;

/**
 * Test role_id behavior on badges.
 *
 * @group user_badges
 */
class BadgeRoleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'system', 'user_badges', 'field', 'options', 'file', 'image'];

  /**
   * @var array
   */
  protected $rids = [];

  /**
   * @var array
   */
  protected $badgeIds = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Installing needed schema.
    $this->installConfig(['user_badges']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('badge');
    $this->installSchema('system', 'sequences');

    $role = Role::create(['id' => $this->randomMachineName()]);
    $role->save();
    $this->rids[] = $role->id();
    $role = Role::create(['id' => $this->randomMachineName()]);
    $role->save();
    $this->rids[] = $role->id();
    foreach ([[], $this->rids[0], $this->rids] as $rids) {
      $badge = Badge::create([
        'type' => 'image_badge',
        'name' => $this->randomString(),
        'role_id' => $rids,
      ]);
      $badge->save();
      $this->badgeIds[] = $badge->id();
    }
  }

  public function testUserPresave() {
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();
    $item_list = $user->get('field_user_badges');
    $this->assertTrue($item_list->isEmpty());
    // This role has only one badge.
    $user->addRole($this->rids[1]);
    $user->save();
    $this->assertEquals($item_list->getValue(), [['target_id' => $this->badgeIds[2]]]);
    // Remove the role, check no badges are left.
    $user->removeRole($this->rids[1]);
    $user->save();
    $this->assertTrue($item_list->isEmpty());
    // This role has two badges.
    $user->addRole($this->rids[0]);
    $user->save();
    $this->assertEquals($item_list->getValue(), [['target_id' => $this->badgeIds[1]], ['target_id' => $this->badgeIds[2]]]);
    // Now add a non-role badge.
    $item_list->appendItem($this->badgeIds[0]);
    // Test for rekey: right now we have badges 1,2, 0.
    $this->assertEquals($item_list->count(), 3);
    for ($i = 0; $i < 3; $i++) {
      $this->assertEquals($item_list->get($i)->getValue(), ['target_id' => $this->badgeIds[($i + 1) % 3]]);
    }
    $user->save();
    $this->assertEquals($item_list->count(), 3);
    // After save we have badges 0, 1, 2.
    for ($i = 0; $i < 3; $i++) {
      $this->assertEquals($item_list->get($i)->getValue(), ['target_id' => $this->badgeIds[($i + 1) % 3]]);
    }
  }
}
