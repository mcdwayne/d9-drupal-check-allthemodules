<?php

namespace Drupal\Test\one_time_password\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Test attaching the field to the user.
 *
 * @group one_time_password
 */
class UserFieldAttachTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'one_time_password',
  ];

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->user = User::create([
      'name' => 'foo',
    ]);
  }

  /**
   * Test the field is attached to the user.
   */
  public function testUserAttachField() {
    $this->assertNotEmpty($this->user->getFieldDefinitions()['one_time_password']);
  }

  /**
   * Test access to the field.
   */
  public function testFieldAccess() {
    $this->user->one_time_password->regenerateOneTimePassword();
    $this->assertNotEmpty($this->user->one_time_password->uri);

    // Ensure the user would normally have access to fields on the user.
    $admin = $this->createUser(['administer users', 'access user profiles']);
    $this->assertTrue($this->user->name->access('view'), $admin);

    // Ensure the one time password field is locked down from all operations.
    $this->assertFalse($this->user->one_time_password->access('view'), $admin);
    $this->assertFalse($this->user->one_time_password->access('update'), $admin);
    $this->assertFalse($this->user->one_time_password->access('create'), $admin);
    $this->assertFalse($this->user->one_time_password->access('delete'), $admin);
  }

}
