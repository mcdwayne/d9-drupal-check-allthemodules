<?php

namespace Drupal\Tests\consistent_uid\Kernel;

use Drupal\consistent_uid\HookHandler\ConsistentUidHookHandler;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Unit tests for consistent_uid module.
 *
 * @group consistent_uid
 */
class ConsistentUidKernelTest extends KernelTestBase {

  /**
   * Modules to be enabled
   *
   * @var array|string[]
   */
  public static $modules = [
    'system',
    'user',
    'consistent_uid',
  ];

  /**
   * Database service.
   *
   * @var Connection
   */
  protected $database;

  /**
   * State service.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setup();
    $this->installConfig(['system']);
    $this->installEntitySchema('user');
    $this->installSchema('user', 'users_data');
    $this->installSchema('system', ['sequences']);
    $this->state = $this->container->get('state');
    $this->assertInstanceOf(StateInterface::class, $this->state, 'State service obtained');
    $this->database = $this->container->get('database');
    $this->assertInstanceOf(Connection::class, $this->database, 'Database service obtained');
  }

  /**
   * Test users create and id increment.
   */
  public function testUsersCreateAndIdIncrement() {
    // Check increment.
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, NULL);
    $this->assertNull($increment, 'Increment not set by default');
    // Create first user.
    $user1 = User::create(['name' => $this->randomMachineName()]);
    $user1->save();
    $this->assertEquals('1', $user1->id(), 'User 1 created with id 1.');
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, -1);
    $this->assertEquals(1, $increment, 'Increment set to current user id');
    // Sequence checking.
    $this->database->query('UPDATE {sequences} SET value = GREATEST(value, 100)');
    $sequence = $this->database->query('SELECT value FROM {sequences}')->fetchField();
    $this->assertFalse($sequence, 'Sequence table has no values after creating user.');
    $this->database->query('INSERT INTO {sequences} (value) VALUES (100)');
    $sequence = $this->database->query('SELECT value FROM {sequences}')->fetchField();
    $this->assertEquals(100, $sequence, 'Sequence increment set to 100.');
    // Create second user.
    $user2 = User::create(['name' => $this->randomMachineName()]);
    $user2->save();
    $this->assertEquals('2', $user2->id(), 'User 2 created with id 2.');
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, NULL);
    $this->assertEquals(2, $increment, 'Increment set to current user id');
    // Delete second user.
    $user2->delete();
    $user2 = User::load(2);
    $this->assertNull($user2, 'User 2 was deleted.');
    // Create third user.
    $user3 = User::create(['name' => $this->randomMachineName()]);
    $user3->save();
    $this->assertEquals('3', $user3->id(), 'User 3 created with id 3.');
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, NULL);
    $this->assertEquals(3, $increment, 'Increment set to current user id');
    // Set increment to 7.
    $this->state->set(ConsistentUidHookHandler::INCREMENT, 7);
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, NULL);
    $this->assertEquals(7, $increment, 'Increment set to 7');
    // Create user based on incremented value.
    $user8 = User::create(['name' => $this->randomMachineName()]);
    $user8->save();
    $this->assertEquals('8', $user8->id(), 'User 8 created with id 8.');
    // Delete increment.
    $this->state->delete(ConsistentUidHookHandler::INCREMENT);
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, NULL);
    $this->assertNull($increment, 'Increment deleted.');
    // Create user based on database value.
    $user9 = User::create(['name' => $this->randomMachineName()]);
    $user9->save();
    $this->assertEquals('9', $user9->id(), 'User 9 created with id 9.');
    $increment = $this->state->get(ConsistentUidHookHandler::INCREMENT, NULL);
    $this->assertEquals(9, $increment, 'Increment set to 9');
    // Ensure we have nothing to do with sequence.
    $sequence = $this->database->query('SELECT value FROM {sequences}')->fetchField();
    $this->assertEquals(100, $sequence, 'Sequence increment set to 100.');
  }

}
