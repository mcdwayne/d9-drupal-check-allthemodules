<?php

namespace Drupal\Tests\context_profile_role\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests that the context profile role's conditions are working properly.
 *
 * @group context_profile_role
 */
class UserProfileRoleConditionTest extends EntityKernelTestBase {

  public static $modules = ['context_profile_role', 'user'];

  /**
   * Tests conditions.
   */
  public function testConditions() {
    /** @var \Drupal\Core\Condition\ConditionManager $manager */
    $manager = $this->container->get('plugin.manager.condition', $this->container->get('container.namespaces'));

    Role::create([
      'id' => 'role_1',
      'label' => 'Role 1',
    ])->save();

    Role::create([
      'id' => 'role_2',
      'label' => 'Role 2',
    ])->save();

    // Create the users required for testing.
    $user_1 = User::create([
      'name' => 'user 1',
      'roles' => [
        'role_1',
      ],
    ]);
    $user_1->save();

    $user_2 = User::create([
      'name' => 'user 2',
      'roles' => [
        'role_2',
      ],
    ]);
    $user_2->save();

    $user_3 = User::create([
      'name' => 'user 3',
      'roles' => [
        'role_1',
        'role_2',
      ],
    ]);
    $user_3->save();

    // Grab the user profile role condition and configure it to check against
    // role 1 on user 1.
    /** @var \Drupal\context_profile_role\Plugin\Condition\UserProfileRole $condition */
    $condition = $manager->createInstance('user_profile_role')
      ->setConfig('roles', ['role_1' => 'role_1'])
      ->setContextValue('user_profile', $user_1);
    $this->assertTrue($condition->execute(), 'Condition is TRUE for user 1 with the role 1 against role 1.');

    // Test with user 2 against role 1.
    $condition->setContextValue('user_profile', $user_2);
    $this->assertFalse($condition->execute(), 'Condition is FALSE for user 2 with role 2 against role 1.');

    // Test with user 2 against role 2.
    $condition->setConfig('roles', ['role_2' => 'role_2']);
    $this->assertTrue($condition->execute(), 'Condition is TRUE for user 2 with role 2 against role 2.');

    // Test with user 3 (multiple roles).
    $condition->setContextValue('user_profile', $user_3);
    $condition->setConfig('roles', ['role_1' => 'role_1']);
    $this->assertTrue($condition->execute(), 'Condition is TRUE for user 3 with role 1 and role 2 against role 1.');
    $condition->setConfig('roles', ['role_2' => 'role_2']);
    $this->assertTrue($condition->execute(), 'Condition is TRUE for user 3 with role 1 and role 2 against role 2.');
    $condition->setConfig('roles', [
      'role_1' => 'role_1',
      'role_2' => 'role_2',
    ]);
    $this->assertTrue($condition->execute(), 'Condition is TRUE for user 3 with role 1 and role 2 against role 1 or role 2.');

    // Test with user 1 against role 1 and role 2.
    $condition->setContextValue('user_profile', $user_1);
    $this->assertTrue($condition->execute(), 'Condition is TRUE for user 1 with role 1 against role 1 or role 2.');

    // Test the negation.
    $condition->setConfig('negate', TRUE);
    $condition->setConfig('roles', ['role_2' => 'role_2']);
    $condition->setContextValue('user_profile', $user_2);
    $this->assertFalse($condition->execute(), 'Condition negated is FALSE for user 2 with role 2 against role 2.');
    $condition->setContextValue('user_profile', $user_1);
    $this->assertTrue($condition->execute(), 'Condition negated is TRUE for user 1 with role 1 against role 2.');
  }

}
