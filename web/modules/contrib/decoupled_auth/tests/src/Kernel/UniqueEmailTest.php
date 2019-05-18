<?php

namespace Drupal\Tests\decoupled_auth\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\decoupled_auth\Tests\DecoupledAuthUserCreationTrait;
use Drupal\decoupled_auth\DecoupledAuthConfig;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the Migration entity.
 *
 * @coversDefaultClass \Drupal\decoupled_auth\Plugin\Validation\Constraint\DecoupledAuthUserMailUniqueValidator
 * @group decoupled_auth
 */
class UniqueEmailTest extends KernelTestBase {

  use DecoupledAuthUserCreationTrait;
  use UserCreationTrait;

  /**
   * Create an unsaved decoupled user.
   *
   * @var bool
   */
  const UNSAVED_USER_DECOUPLED = TRUE;

  /**
   * Create an unsaved coupled user.
   *
   * @var bool
   */
  const UNSAVED_USER_COUPLED = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['decoupled_auth', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['decoupled_auth']);
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
  }

  /**
   * Set the configuration for unique emails.
   *
   * @param string $mode
   *   The mode. Can be one of the
   *   \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_*
   *   constants.
   * @param array $roles
   *   The array of role IDs if in
   *   \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE
   *   or
   *   \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE
   *   mode.
   */
  protected function setUniqueEmailsConfig($mode, array $roles = []) {
    $this->config('decoupled_auth.settings')
      ->set('unique_emails.mode', $mode)
      ->set('unique_emails.roles', $roles)
      ->save();
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS
   * mode with no existing users.
   *
   * @covers ::validate
   */
  public function testModeAllNone() {
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS);

    // Test validating a decoupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a coupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS
   * mode with an existing decoupled user.
   *
   * @covers ::validate
   */
  public function testModeAllDecoupled() {
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS);

    $existing_user = $this->createDecoupledUser();

    // Test validating a decoupled user.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user fails validation.');

    // Test validating a coupled user.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS
   * mode with an existing coupled user.
   *
   * @covers ::validate
   */
  public function testModeAllCoupled() {
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS);

    $existing_user = $this->createUser();

    // Test validating a decoupled user.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user fails validation.');

    // Test validating a coupled user.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED mode
   * with no existing users.
   *
   * @covers ::validate
   */
  public function testModeNoneNone() {
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED);

    // Test validating a decoupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a coupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED mode
   * with an existing decoupled user.
   *
   * @covers ::validate
   */
  public function testModeNoneDecoupled() {
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED);

    $existing_user = $this->createDecoupledUser();

    // Test validating a decoupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a coupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED mode
   * with an existing coupled user.
   *
   * @covers ::validate
   */
  public function testModeNoneCoupled() {
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED);

    $existing_user = $this->createUser();

    // Test validating a decoupled user.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a coupled user.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE
   * mode with no existing users.
   *
   * @covers ::validate
   */
  public function testModeWithRoleNone() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE, [$role]);

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');

    // Test validating a coupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE
   * mode with an existing decoupled user with no matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithRoleDecoupledWithoutRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE, [$role]);

    $existing_user = $this->createDecoupledUser();

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user without role passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user with role passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user without the role passes validation.');

    // Test validating a coupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user with the role passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE
   * mode with an existing decoupled user with a matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithRoleDecoupledWithRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE, [$role]);

    $existing_user = $this->createDecoupledUser();
    $existing_user->addRole($role);
    $existing_user->save();

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user without role passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user with role fails validation.');

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user without the role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user with the role fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE
   * mode with an existing coupled user with no matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithRoleCoupledWithoutRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE, [$role]);

    $existing_user = $this->createUser();

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user without role passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user with role fails validation.');

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user without the role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user with the role fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE
   * mode with an existing coupled user with a matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithRoleCoupledWithRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE, [$role]);

    $existing_user = $this->createUser();
    $existing_user->addRole($role);
    $existing_user->save();

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user without role passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user with role fails validation.');

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user without the role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user with the role fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE
   * mode with no existing users.
   *
   * @covers ::validate
   */
  public function testModeWithoutRoleNone() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE, [$role]);

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');

    // Test validating a coupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE
   * mode with an existing decoupled user with no matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithoutRoleDecoupledWithoutRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE, [$role]);

    $existing_user = $this->createDecoupledUser();

    // Test validating a decoupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user without role fails validation.');

    // Test validating a decoupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user with role passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user without the role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user with the role fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE
   * mode with an existing decoupled user with a matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithoutRoleDecoupledWithRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE, [$role]);

    $existing_user = $this->createDecoupledUser();
    $existing_user->addRole($role);
    $existing_user->save();

    // Test validating a decoupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user without role passes validation.');

    // Test validating a decoupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user with role passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user without the role passes validation.');

    // Test validating a coupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->email_prefix);
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Coupled user with the role passes validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE
   * mode with an existing coupled user with no matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithoutRoleCoupledWithoutRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE, [$role]);

    $existing_user = $this->createUser();

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user without role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user with role passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user without the role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user with the role fails validation.');
  }

  /**
   * Test the unique email validator.
   *
   * \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE
   * mode with an existing coupled user with a matching roles.
   *
   * @covers ::validate
   */
  public function testModeWithoutRoleCoupledWithRole() {
    $role = $this->createRole([]);
    $this->setUniqueEmailsConfig(DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE, [$role]);

    $existing_user = $this->createUser();
    $existing_user->addRole($role);
    $existing_user->save();

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Decoupled user without role passes validation.');

    // Test validating a coupled user with the role.
    // Expected result: No violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_DECOUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertEmpty($violations, 'Decoupled user with role passes validation.');

    // Test validating a coupled user without the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user without the role fails validation.');

    // Test validating a coupled user with the role.
    // Expected result: Violations.
    $user = $this->createUnsavedUser(self::UNSAVED_USER_COUPLED, $existing_user->getAccountName());
    $user->addRole($role);
    $violations = $user->validate();
    $this->assertNotEmpty($violations, 'Coupled user with the role fails validation.');
  }

}
