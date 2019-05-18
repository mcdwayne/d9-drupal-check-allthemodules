<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests that UserExistsByName service doesn't cause exceptions.
 *
 * @group entity_pilot
 */
class UserExistsByNameTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot',
    'serialization',
    'rest',
    'hal',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests user exists by name.
   */
  public function testUserExistsByName() {
    $exists = User::create([
      'name' => 'foo',
      'mail' => 'foo@bar.com',
      'status' => 0,
    ]);
    $exists->save();

    $does_not_exist = User::create([
      'name' => 'bar',
      'mail' => 'bar@foo.com',
    ]);

    $manager = \Drupal::service('plugin.manager.entity_pilot.exists');
    $user_exists_by_name = $manager->createInstance('user_exists_by_name');

    $new = User::create([
      'name' => 'foo',
      'mail' => 'foo@bar.com',
      'status' => 1,
    ]);

    $entity_manager = \Drupal::entityManager();
    $this->assertFalse($user_exists_by_name->exists($entity_manager, $does_not_exist));
    $this->assertEquals($exists->toArray(), $user_exists_by_name->exists($entity_manager, $new)->toArray());
    $user_exists_by_name->preApprove($new, $exists);
    $new->save();

    $user_storage = $entity_manager->getStorage('user');
    $user_storage->resetCache([$exists->id()]);
    $fresh = $user_storage->load($exists->id());
    // Status field should be updated.
    $this->assertTrue($fresh->isActive());
  }

}
