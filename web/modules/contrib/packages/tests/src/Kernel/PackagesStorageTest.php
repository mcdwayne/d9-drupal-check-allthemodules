<?php

namespace Drupal\Tests\packages\Kernel;

use Drupal\Tests\packages\Kernel\PackagesTestBase;
use Drupal\packages\PackageState;
use Drupal\packages\PackageStorage;
use Drupal\packages\PackageStorageException;

/**
 * Tests Packages storage service.
 *
 * @group packages
 */
class PackagesStorageTest extends PackagesTestBase {

  /**
   * The packages storage service.
   *
   * @var \Drupal\packages\PackagesStorageInterface
   */
  protected $packageStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installSchema('packages', 'packages');
    $this->packageStorage = \Drupal::service('package.storage');
  }

  /**
   * Tests saving data for an anonymous user.
   */
  public function testAnonymousSave() {
    try {
      // Saving for anonymous should throw an exception.
      $states = $this->packages->getStates();
      $this->packageStorage->save($states);
      $this->assertTrue(FALSE);
    }
    catch (PackageStorageException $e) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Tests saving data non-state data.
   */
  public function testNonStateSave() {
    try {
      // Only an array of package states can be saved.
      $states = [(object) ['abc' => 'def']];
      $this->packageStorage->save($states);
      $this->assertTrue(FALSE);
    }
    catch (PackageStorageException $e) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Tests the setting of the user.
   */
  public function testUserSet() {
    // Since no user is logged in, the service should return a user ID of 0.
    $this->assertEquals($this->packageStorage->getUserId(), 0);

    // Create a user and set them as the current user.
    $user = $this->createUser();
    $this->logIn($user);

    // Reset the storage user and check that it matches the new user.
    $this->packageStorage->setUserId();
    $this->assertEquals($this->packageStorage->getUserId(), $user->id());

    // Check that any user ID can be set.
    $this->packageStorage->setUserId(123);
    $this->assertEquals($this->packageStorage->getUserId(), 123);
  }

  /**
   * Tests the storage operations.
   */
  public function testStorage() {
    $hashes = [];

    // Generate and store random package states for 5 users.
    for ($i = 1; $i <= 5; $i++) {
      // Set storage for an arbitrary user ID.
      $this->packageStorage->setUserId($i);

      // Get the current package states.
      $state = new PackageState($this->randomMachineName());
      $states = [$state->getPackageId() => $state];

      // Storage a hash of the states.
      $hashes[$i] = md5(serialize($states));

      // Save the states.
      $this->packageStorage->save($states);
    }

    // Iterate over two sample users.
    foreach ([2, 5] as $uid) {
      // Load the states.
      $states = $this->packageStorage->setUserId($uid)->load();

      // Make sure the hash is a match.
      $this->assertEquals(md5(serialize($states)), $hashes[$uid]);
    }

    // Clear the static cache.
    drupal_static_reset(PackageStorage::STATIC_CACHE_KEY);

    // Iterate over two other users.
    foreach ([3, 4] as $uid) {
      // Load the states.
      $states = $this->packageStorage->setUserId($uid)->load();

      // Make sure the hash is a match.
      $this->assertEquals(md5(serialize($states)), $hashes[$uid]);
    }

    // Delete the states for user 1.
    $this->packageStorage->setUserId(1)->delete();

    // Load for user 1 and make sure there is nothing stored.
    $this->assertEquals(count($this->packageStorage->load()), 0);

    // Clear the static cache.
    drupal_static_reset(PackageStorage::STATIC_CACHE_KEY);

    // Retest the load.
    $this->assertEquals(count($this->packageStorage->load()), 0);

    // Load the states for user 3 to populate the static cache.
    $states = $this->packageStorage->setUserId(3)->load();

    // Manually delete the states for user 3.
    \Drupal::service('database')
      ->delete(PackageStorage::TABLE)
      ->condition('uid', 3)
      ->execute();

    // Load the states for user 3. The data should still be statically cached
    // since we didn't use the packages service to delete.
    $states = $this->packageStorage->load();

    // Make sure the hash is a match.
    $this->assertEquals(md5(serialize($states)), $hashes[3]);
  }

  /**
   * Tests user deletion.
   */
  public function testUserDelete() {
    // Create a test user.
    $user = $this->createUser();
    $this->logIn($user);

    // Store the user ID.
    $uid = $user->id();

    // Save default package states in the database.
    $this->packageStorage->setUserId();
    $this->packages->saveStates();

    // Delete the user.
    $user->delete();

    // Load the states for this user from the database, and ensure they have
    // been deleted now via packages_entity_delete().
    $this->assertTrue(empty($this->packageStorage->load()));

    // Clear the static cache, just to be sure.
    drupal_static_reset(PackageStorage::STATIC_CACHE_KEY);

    // Test the load once more time.
    $this->assertTrue(empty($this->packageStorage->load()));
  }

}
