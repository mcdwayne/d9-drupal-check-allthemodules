<?php

namespace Drupal\Tests\mass_contact\Kernel\Plugin\MassContact\GroupingMethod;

use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\mass_contact\Kernel\MassContactTestBase;
use Drupal\user\Entity\Role as UserRole;
use Drupal\user\RoleInterface;

/**
 * Tests the role grouping method plugin.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\Role
 */
class RoleTest extends MassContactTestBase {

  use UserCreationTrait;

  /**
   * Some users to test with.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $recipients;

  /**
   * Some roles to test with.
   *
   * @var \Drupal\user\RoleInterface[]
   */
  protected $roles;

  /**
   * The grouping plugin manager.
   *
   * @var \Drupal\Core\Plugin\DefaultPluginManager
   */
  protected $groupingManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    // Add 3 roles.
    foreach (range(1, 3) as $i) {
      $rid = $this->createRole([]);
      $this->roles[$i] = UserRole::load($rid);
    }

    // Create 10 users, and add their roles.
    foreach (range(1, 10) as $i) {
      $this->recipients[$i] = $this->createUser([]);
      $this->recipients[$i]->addRole($this->roles[2]->id());
      // Block user 4.
      if ($i == 4) {
        $this->recipients[$i]->block();
      }
      $this->recipients[$i]->save();
    }

    $this->groupingManager = \Drupal::service('plugin.manager.mass_contact.grouping_method');
  }

  /**
   * Test gathering recipients.
   *
   * @covers ::getRecipients
   */
  public function testGetRecipients() {
    // Test authenticated role special behavior.
    $config = [
      'conjunction' => 'OR',
      'categories' => [
        RoleInterface::AUTHENTICATED_ID,
        $this->roles[3]->id(),
      ],
    ];
    /** @var \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\Role $instance */
    $instance = $this->groupingManager->createInstance('role', $config);
    $this->assertEquals(9, count($instance->getRecipients($config['categories'])));

    // Switch conjunction.
    $config['conjunction'] = 'AND';
    /** @var \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\Role $instance */
    $instance = $this->groupingManager->createInstance('role', $config);
    $this->assertEmpty($instance->getRecipients($config['categories']));

    // Add role 3 to 2 users.
    foreach ([7, 9] as $uid) {
      $this->recipients[$uid]->addRole($this->roles[3]->id());
      $this->recipients[$uid]->save();
    }
    // Add role 1 to user 7.
    $this->recipients[7]->addRole($this->roles[1]->id());
    $this->recipients[7]->save();

    /** @var \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\Role $instance */
    $instance = $this->groupingManager->createInstance('role', $config);
    $this->assertEquals(2, count($instance->getRecipients($config['categories'])));

    $config['categories'] = [
      $this->roles[3]->id(),
      $this->roles[1]->id(),
    ];
    /** @var \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\Role $instance */
    $instance = $this->groupingManager->createInstance('role', $config);
    $this->assertEquals(1, count($instance->getRecipients($config['categories'])));

    // Switch back to OR.
    $config['conjunction'] = 'OR';
    /** @var \Drupal\mass_contact\Plugin\MassContact\GroupingMethod\Role $instance */
    $instance = $this->groupingManager->createInstance('role', $config);
    $this->assertEquals(2, count($instance->getRecipients($config['categories'])));
  }

}
