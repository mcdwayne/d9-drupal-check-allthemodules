<?php

namespace Drupal\Tests\decoupled_auth\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests Profile integration.
 *
 * @group decoupled_auth
 */
class UserProfileFieldTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['decoupled_auth', 'field', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
  }

  /**
   * Enable and install config for profile module.
   *
   * @param bool $config
   *   Whether to install profile config.
   */
  protected function installProfile($config = FALSE) {
    $this->enableModules(['profile', 'entity', 'views']);
    $this->installEntitySchema('profile');
    if ($config) {
      $this->installConfig(['profile']);
    }
  }

  /**
   * Check whether the definition for a field exists for a profile type.
   *
   * @param string $profile_type
   *   The name of the profile type to check for.
   *
   * @return bool
   *   Whether the field exists.
   */
  protected function profileFieldDefinitionExists($profile_type) {
    $entity_manager = $this->container->get('entity.manager');
    $definitions = $entity_manager->getFieldStorageDefinitions('user');
    return isset($definitions['profile_' . $profile_type]);
  }

  /**
   * Check whether the schema for a profile field exists.
   *
   * @param string $profile_type
   *   The name of the profile type to check for.
   *
   * @return bool
   *   Whether the schema exists.
   */
  protected function profileFieldSchemaExists($profile_type) {
    $schema = $this->container->get('database')->schema();
    return $schema->tableExists('user__profile_' . $profile_type);
  }

  /**
   * Create and save a profile.
   *
   * @param string $type
   *   One of 'test_single' or 'test_mulitple'.
   * @param \Drupal\user\UserInterface $user
   *   The loaded user entity who owns this object.
   * @param bool $active
   *   Whether the profile should be created as active.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The saved profile entity.
   */
  protected function createProfile($type, UserInterface $user, $active) {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create(['type' => $type]);
    $profile->setOwner($user);
    $profile->test_field = $this->randomMachineName();
    $profile->setActive($active);
    $profile->save();
    return $profile;
  }

  /**
   * Test that the profile field gets created and deleted correctly via the API.
   */
  public function testFieldCreateDeleteApi() {
    $this->installProfile();

    // Check that the field doesn't currently exist.
    $this->assertFalse($this->profileFieldDefinitionExists('test'), 'Test profile field definition does not exist.');
    $this->assertFalse($this->profileFieldSchemaExists('test'), 'Test profile field schema does not exist.');

    // Add the profile type.
    $profile_type = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $profile_type->save();

    // Check that the field now exist.
    $this->assertTrue($this->profileFieldDefinitionExists('test'), 'Test profile field definition has been created.');
    $this->assertTrue($this->profileFieldSchemaExists('test'), 'Test profile field schema has been created.');

    // Delete the profile type.
    $profile_type->delete();

    // Check that the field now exist.
    $this->assertFalse($this->profileFieldDefinitionExists('test'), 'Test profile field definition has been deleted.');
    $this->assertFalse($this->profileFieldSchemaExists('test'), 'Test profile field schema has been deleted.');
  }

  /**
   * Test that the profile fields get created from installing config.
   */
  public function testFieldCreateConfig() {
    $this->installProfile();

    // Check that the field doesn't currently exist.
    $this->assertFalse($this->profileFieldDefinitionExists('test_single'), 'Test single profile field definition does not exist.');
    $this->assertFalse($this->profileFieldSchemaExists('test_single'), 'Test single profile field schema does not exist.');

    // Install the config.
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Check that the field now exist.
    $this->assertTrue($this->profileFieldDefinitionExists('test_single'), 'Test single profile field definition has been created.');
    $this->assertTrue($this->profileFieldSchemaExists('test_single'), 'Test single profile field schema has been created.');
  }

  /**
   * Test that fields get created from installing profile with optional config.
   */
  public function testFieldCreateEnableProfile() {
    // Enable our module with optional configuration.
    $this->enableModules(['decoupled_auth_profile_test']);

    // Check that the field doesn't currently exist.
    $this->assertFalse($this->profileFieldDefinitionExists('test_single'), 'Test single profile field definition does not exist.');
    $this->assertFalse($this->profileFieldSchemaExists('test_single'), 'Test single profile field schema does not exist.');

    // Install profile.
    $this->installProfile(TRUE);

    // Check that the field now exist.
    $this->assertTrue($this->profileFieldDefinitionExists('test_single'), 'Test single profile field definition has been created.');
    $this->assertTrue($this->profileFieldSchemaExists('test_single'), 'Test single profile field schema has been created.');
  }

  /**
   * Test that CRUD operations on a profile updates the appropriate field.
   */
  public function testFieldPopulationSingle() {
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create our user.
    $user = $this->createUser();

    // Check that there is no profile on the user.
    $this->assertNull($user->profile_test_single->target_id, 'Test single profile does not exist on the user.');

    // Create the profile.
    $profile = $this->createProfile('test_single', $user, FALSE);

    // Check that there is no profile on the user as it's not active.
    $this->assertNull($user->profile_test_single->target_id, 'Test single inactive profile does not exist on the user.');

    // Make the profile active.
    $profile->setActive(TRUE);
    $profile->save();

    // Check that the field has been filled out correctly.
    $user = User::load($user->id());
    $this->assertEquals($profile->id(), $user->profile_test_single->target_id, 'Test single profile has been stored on the user.');
    $this->assertInstanceOf(get_class($profile), $user->profile_test_single->entity, 'Test single profile is accessible from the user.');
    $this->assertEquals($profile->test_field->value, $user->profile_test_single->entity->test_field->value, 'Test single profile test field matches.');

    // Delete the profile.
    $profile->delete();

    // Check that there is a profile on the user.
    $user = User::load($user->id());
    $this->assertNull($user->profile_test_single->target_id, 'Test single profile has been removed from the user.');
  }

  /**
   * Test that the delete operation on a profile via a user deletion.
   */
  public function testFieldDeleteUser() {
    $this->installSchema('user', 'users_data');
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create our user.
    $user = $this->createUser();

    // Create the profile.
    $profile = $this->createProfile('test_single', $user, TRUE);

    // Check that the field has been filled out correctly.
    $user = User::load($user->id());
    $this->assertEquals($profile->id(), $user->profile_test_single->target_id, 'Test single profile has been stored on the user.');
    $this->assertInstanceOf(get_class($profile), $user->profile_test_single->entity, 'Test single profile is accessible from the user.');
    $this->assertEquals($profile->test_field->value, $user->profile_test_single->entity->test_field->value, 'Test single profile test field matches.');

    // Delete the user.
    $user->delete();
  }

  /**
   * Test that CRUD operations on a profile updates the appropriate field.
   */
  public function testFieldPopulationMultiple() {
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create our user.
    $user = $this->createUser();

    // Check that there are no profiles on the user.
    $this->assertEquals(0, $user->profile_test_multiple->count(), 'Test multiple profile does not exist on the user.');

    // Create the profiles.
    $profile1 = $this->createProfile('test_multiple', $user, TRUE);
    $profile2 = $this->createProfile('test_multiple', $user, FALSE);

    // Check that the field has been filled out with only the active profile.
    $user = User::load($user->id());
    $this->assertEquals(1, $user->profile_test_multiple->count(), 'One test multiple profile has been stored on the user.');
    $this->assertEquals($profile1->id(), $user->profile_test_multiple->get(0)->target_id, 'Test multiple profile 1 has been stored on the user.');
    $this->assertInstanceOf(get_class($profile1), $user->profile_test_multiple->get(0)->entity, 'Test multiple profile 1 is accessible from the user.');
    $this->assertEquals($profile1->test_field->value, $user->profile_test_multiple->get(0)->entity->test_field->value, 'Test multiple profile 1 test field matches.');
    $this->assertNull($user->profile_test_multiple->get(1), 'Test multiple profile 2 does not exist on the user.');

    // Make the second profile active.
    $profile2->setActive(TRUE);
    $profile2->save();

    // Check that the field has been filled out correctly.
    $user = User::load($user->id());
    $this->assertEquals(2, $user->profile_test_multiple->count(), 'Two test multiple profile has been stored on the user.');
    $this->assertEquals($profile1->id(), $user->profile_test_multiple->get(0)->target_id, 'Test multiple profile 1 has been stored on the user.');
    $this->assertInstanceOf(get_class($profile1), $user->profile_test_multiple->get(0)->entity, 'Test multiple profile 1 is accessible from the user.');
    $this->assertEquals($profile1->test_field->value, $user->profile_test_multiple->get(0)->entity->test_field->value, 'Test multiple profile 1 test field matches.');
    $this->assertEquals($profile2->id(), $user->profile_test_multiple->get(1)->target_id, 'Test multiple profile 2 has been stored on the user.');
    $this->assertInstanceOf(get_class($profile2), $user->profile_test_multiple->get(1)->entity, 'Test multiple profile 2 is accessible from the user.');
    $this->assertEquals($profile2->test_field->value, $user->profile_test_multiple->get(1)->entity->test_field->value, 'Test multiple profile 2 test field matches.');

    // Delete the first profile.
    $profile1->delete();

    // Check that the deleted profile has been removed.
    $user = User::load($user->id());
    $this->assertEquals(1, $user->profile_test_multiple->count(), 'One test multiple profile has been removed from the user.');
    $this->assertEquals($profile2->id(), $user->profile_test_multiple->get(0)->target_id, 'Test multiple profile 1 has been removed from the user.');

    // Delete the second profile.
    $profile2->delete();

    // Check that there are no profiles on the user.
    $user = User::load($user->id());
    $this->assertEquals(0, $user->profile_test_multiple->count(), 'Both test multiple profile has been removed from the user.');
  }

  /**
   * Test query condition on a field on a single profile.
   */
  public function testEntityQuerySingleCondition() {
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create the user and profile.
    $user = $this->createUser();
    $profile = $this->createProfile('test_single', $user, TRUE);

    // Create an additional users.
    $this->createProfile('test_single', $this->createUser(), TRUE);

    // Test that we can put a condition on the test field.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_single.entity.test_field', $profile->test_field->value);
    $result = $query->execute();
    $this->assertEquals(1, count($result), 'One user found.');
    $this->assertEquals($user->id(), reset($result), 'Correct user found.');
  }

  /**
   * Test query sorting on a field on a single profile.
   */
  public function testEntityQuerySingleSort() {
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create some users and profiles.
    $this->createProfile('test_single', $this->createUser(), TRUE);
    $this->createProfile('test_single', $this->createUser(), TRUE);
    $this->createProfile('test_single', $this->createUser(), TRUE);
    $this->createProfile('test_single', $this->createUser(), TRUE);
    $this->createProfile('test_single', $this->createUser(), TRUE);

    // Test that we can sort based on the test field.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->exists('profile_test_single.entity.test_field');
    $query->sort('profile_test_single.entity.test_field');
    $result = $query->execute();
    $this->assertEquals(5, count($result), 'Three users found.');

    // Get the test field values out of the result to check the sort.
    $result_values = [];
    foreach (User::loadMultiple($result) as $user) {
      // Need to do strtolower() as mysql is case insensitive but php doesn't.
      $result_values[] = strtolower($user->profile_test_single->entity->test_field->value);
    }
    $this->assertGreaterThanOrEqual($result_values[0], $result_values[1], 'Returned in correct order.');
    $this->assertGreaterThanOrEqual($result_values[1], $result_values[2], 'Returned in correct order.');
  }

  /**
   * Test query condition on a field on a multiple profile.
   */
  public function testEntityQueryMultipleCondition() {
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create the user and profile.
    $user = $this->createUser();
    $profile1 = $this->createProfile('test_multiple', $user, TRUE);
    $profile2 = $this->createProfile('test_multiple', $user, TRUE);

    // Create an additional users.
    $this->createProfile('test_multiple', $this->createUser(), TRUE);

    // Test that we can put a condition on the test field from $profile1.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_multiple.entity.test_field', $profile1->test_field->value);
    $result = $query->execute();
    $this->assertEquals(1, count($result), 'One user found using first profile.');
    $this->assertEquals($user->id(), reset($result), 'Correct user found using first profile.');

    // Test that we can put a condition on the test field from $profile2.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_multiple.entity.test_field', $profile2->test_field->value);
    $result = $query->execute();
    $this->assertEquals(1, count($result), 'One user found using second profile.');
    $this->assertEquals($user->id(), reset($result), 'Correct user found using second profile.');
  }

  /**
   * Test query condition on a field on a multiple profile with delta.
   */
  public function testEntityQueryMultipleConditionWithDelta() {
    // This test is dependent on https://www.drupal.org/node/2384459, so we will
    // skip it until that is committed.
    $this->markTestSkipped('Skipped pending https://www.drupal.org/node/2384459');

    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create the user and profile.
    $user = $this->createUser();
    $profile1 = $this->createProfile('test_multiple', $user, TRUE);
    $profile2 = $this->createProfile('test_multiple', $user, TRUE);

    // Create an additional users.
    $this->createProfile('test_multiple', $this->createUser(), TRUE);

    // Test that we can put a condition on the test field from $profile1.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_multiple.0.target_id', $profile1->id());
    $result = $query->execute();
    $this->assertEquals(1, count($result), 'One user found using first profile.');
    $this->assertEquals($user->id(), reset($result), 'Correct user found using first profile.');

    // Test that we can put a condition on the test field from $profile1.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_multiple.0.entity.test_field', $profile1->test_field->value);
    $result = $query->execute();
    $this->assertEquals(1, count($result), 'One user found using first profile.');
    $this->assertEquals($user->id(), reset($result), 'Correct user found using first profile.');

    // Test that we can put a condition on the test field from $profile2.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_multiple.1.entity.test_field', $profile2->test_field->value);
    $result = $query->execute();
    $this->assertEquals(1, count($result), 'One user found using second profile.');
    $this->assertEquals($user->id(), reset($result), 'Correct user found using second profile.');

    // Test that conditions don't work on the wrong delta.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->condition('profile_test_multiple.0.entity.test_field', $profile2->test_field->value);
    $result = $query->execute();
    $this->assertEquals(0, count($result), 'No user found with the wrong delta.');
  }

  /**
   * Test query sorting on a field on a multiple profile.
   */
  public function testEntityQueryMultipleSort() {
    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create some users and profiles.
    $user1 = $this->createUser();
    $this->createProfile('test_multiple', $user1, TRUE);
    $this->createProfile('test_multiple', $user1, TRUE);

    $user2 = $this->createUser();
    $this->createProfile('test_multiple', $user2, TRUE);
    $this->createProfile('test_multiple', $user2, TRUE);

    $user3 = $this->createUser();
    $this->createProfile('test_multiple', $user3, TRUE);
    $this->createProfile('test_multiple', $user3, TRUE);

    $user4 = $this->createUser();
    $this->createProfile('test_multiple', $user4, TRUE);
    $this->createProfile('test_multiple', $user4, TRUE);

    $user5 = $this->createUser();
    $this->createProfile('test_multiple', $user5, TRUE);
    $this->createProfile('test_multiple', $user5, TRUE);

    // Test that we can sort based on the test field.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->exists('profile_test_multiple.entity.test_field');
    $query->sort('profile_test_multiple.entity.test_field');
    $result = $query->execute();
    $this->assertEquals(5, count($result), 'Three users found.');

    // Get the test field values out of the result to check the sort.
    $result_values = [];
    foreach (User::loadMultiple($result) as $user) {
      $value = NULL;
      foreach ($user->profile_test_multiple as $profile_item) {
        // Need to do strtolower() as mysql is case insensitive but php doesn't.
        $value = isset($value) ? min($value, strtolower($profile_item->entity->test_field->value)) : $profile_item->entity->test_field->value;
      }
      $result_values[] = $value;
    }
    $this->assertGreaterThanOrEqual($result_values[0], $result_values[1], 'Returned in correct order.');
    $this->assertGreaterThanOrEqual($result_values[1], $result_values[2], 'Returned in correct order.');
  }

  /**
   * Test query sorting on a field on a multiple profile with delta.
   */
  public function testEntityQueryMultipleSortWithDelta() {
    // This test is dependent on https://www.drupal.org/node/2384459, so we will
    // skip it until that is committed.
    $this->markTestSkipped('Skipped pending https://www.drupal.org/node/2384459');

    $this->installProfile();
    $this->enableModules(['decoupled_auth_profile_test']);
    $this->installConfig(['decoupled_auth_profile_test']);

    // Create some users and profiles.
    $user1 = $this->createUser();
    $this->createProfile('test_multiple', $user1, TRUE);
    $this->createProfile('test_multiple', $user1, TRUE);

    $user2 = $this->createUser();
    $this->createProfile('test_multiple', $user2, TRUE);
    $this->createProfile('test_multiple', $user2, TRUE);

    $user3 = $this->createUser();
    $this->createProfile('test_multiple', $user3, TRUE);
    $this->createProfile('test_multiple', $user3, TRUE);

    $user4 = $this->createUser();
    $this->createProfile('test_multiple', $user4, TRUE);
    $this->createProfile('test_multiple', $user4, TRUE);

    $user5 = $this->createUser();
    $this->createProfile('test_multiple', $user5, TRUE);
    $this->createProfile('test_multiple', $user5, TRUE);

    // Test that we can sort based on the test field.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->container->get('entity.query')->get('user');
    $query->exists('profile_test_multiple.entity.test_field');
    $query->sort('profile_test_multiple.1.entity.test_field');
    $result = $query->execute();
    $this->assertEquals(5, count($result), 'Three users found.');

    // Get the test field values out of the result to check the sort.
    $result_values = [];
    foreach (User::loadMultiple($result) as $user) {
      // Need to do strtolower() as mysql is case insensitive but php doesn't.
      $result_values[] = strtolower($user->profile_test_multiple->get(1)->entity->test_field->value);
    }
    $this->assertGreaterThanOrEqual($result_values[0], $result_values[1], 'Returned in correct order.');
    $this->assertGreaterThanOrEqual($result_values[1], $result_values[2], 'Returned in correct order.');
  }

}
