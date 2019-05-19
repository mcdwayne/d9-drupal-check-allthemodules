<?php

namespace Drupal\Tests\uuid_map\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\uuid_map\UuidMapper;

/**
 * Demonstrate manipulating fixture data in a kernel test.
 *
 * Kernel tests are used where APIs will be invoked, but the results of an HTTP
 * request do not need to be examined.
 *
 * This example will show some techniques for manipulating a fixture and then
 * testing the result. A 'fixture' is some data you set up in a consistent way,
 * so that you can run tests against them.
 *
 * @group uuid_map
 *
 * @ingroup uuid_map
 */
class UuidMapperTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'node', 'uuid_map'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('uuid_map', ['uuid_map']);    
    $this->installEntitySchema('user');
  }
  
  /**
   * Query database for a given uuid.
   */
  protected function selectUuid($uuid) {
    $query = \Drupal::database()->select(UuidMapper::TABLE_NAME, 'base')
      ->condition('base.uuid', $uuid);
    return $query->execute()->fetchAllAssoc();
  }
  
  /**
   * Test data insertion.
   */
  public function testEntityQuery() {
    // Create new user instance.
    $user = User::create([
      'name' => $this->randomMachineName(),
    ]);
    $results = $this->selectUuid($user->uuid());
    $this->assertTrue(count($results) === 0, 'Records with the new uuids should not exist before saving the entity. ' . count($results) . ' exist.');
    
    // Save user.
    $user->save();
    $results = $this->selectUuid($user->uuid());
    $this->assertTrue(count($results) === 1, 'There should be exactly one record with th given uuid. ' . count($results) . ' exist.');
    
    // Save user.
    $user->delete();
    $results = $this->selectUuid($user->uuid());
    $this->assertTrue(count($results) === 0, 'Uuid record should be deleted with the entity. ' . count($results) . ' exist.');
  }
  
}
