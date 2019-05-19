<?php

namespace Drupal\Tests\system_user\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\null_user\NullUser;
use Drupal\system_user\Service\SystemUserManager;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * SystemUserTest class.
 *
 * @group system_user
 */
class SystemUserTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system_user',
    'user',
  ];

  /**
   * The system user service.
   *
   * @var \Drupal\system_user\Service\SystemUserManager
   */
  private $systemUser;

  /**
   * An array of test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  private $users = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);

    $this->systemUser = $this->container->get('system_user.system_user');

    \Drupal::service('config.factory')
      ->getEditable('system.site')
      ->set('name', 'Drupal 8 test site')
      ->save();

    module_load_install('system_user');
    system_user_install();
  }

  /**
   * Test that all system users can be retrieved.
   */
  public function testAllUsersCanBeRetrieved() {
    $this->assertNotEmpty($this->systemUser->getAll(), 'No system users found.');

    // One system user is created by default.
    $this->assertCount(1, $this->systemUser->getAll());

    $this->createUsers();

    $this->assertCount(4, $this->systemUser->getAll());
  }

  /**
   * Test that a single system user can be retrieved.
   */
  public function testFirstUserCanBeRetrieved() {
    $user = $this->systemUser->getFirst();
    $this->assertInstanceOf(UserInterface::class, $user);

    $this->assertEquals(1, $user->id());
    $this->assertEquals('Drupal 8 test site system user', $user->getDisplayName());
  }

  /**
   * Test that a user can be identified as a system user.
   */
  public function testSystemUserCheck() {
    $this->createUsers();

    $this->assertFalse(SystemUserManager::isSystemUser($this->users[0]));

    $this->assertTrue(SystemUserManager::isSystemUser($this->users[1]));
  }

  /**
   * Test that a NullUser is returned if no system users are found.
   */
  public function testNullUserIsReturnedIfEmpty() {
    $user = $this->systemUser->getFirst();

    $this->assertInstanceOf(User::class, $user);

    user_delete($user->id());

    $this->assertInstanceOf(NullUser::class, $this->systemUser->getFirst());
  }

  /**
   * Create some test users.
   */
  private function createUsers() {
    $this->users[] = $this->createUser([SystemUserManager::FIELD_NAME => FALSE]);
    $this->users[] = $this->createUser([SystemUserManager::FIELD_NAME => TRUE]);
    $this->users[] = $this->createUser([SystemUserManager::FIELD_NAME => FALSE]);
    $this->users[] = $this->createUser([SystemUserManager::FIELD_NAME => TRUE]);
    $this->users[] = $this->createUser([SystemUserManager::FIELD_NAME => FALSE]);
    $this->users[] = $this->createUser([SystemUserManager::FIELD_NAME => TRUE]);
  }

}
