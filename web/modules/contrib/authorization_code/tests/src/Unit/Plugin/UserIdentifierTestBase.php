<?php

namespace Drupal\Tests\authorization_code\Unit\Plugin;

use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;

/**
 * Abstract base class for user identifiers.
 */
abstract class UserIdentifierTestBase extends UnitTestCase {

  const TEST_USERS_COUNT = 20;

  /**
   * Mock of a container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $userStorage = $this->createMock(UserStorageInterface::class);
    $userStorage
      ->method($this->storageLoadMethodName())
      ->will($this->returnValueMap($this->userStorageLoadValueMap()));

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager
      ->method('getStorage')
      ->with('user')
      ->willReturn($userStorage);

    $this->container = new ContainerBuilder();
    $this->container->set('entity_type.manager', $entityTypeManager);
    \Drupal::setContainer($this->container);
  }

  /**
   * Test load user when the user is found.
   *
   * @param mixed $identifier
   *   The user identifier.
   * @param \Drupal\user\UserInterface $expected_user
   *   The expected user.
   *
   * @dataProvider validUsers
   */
  public function testLoadUserWhenFound($identifier, UserInterface $expected_user) {
    $this->assertSame(
      $this->getUserIdentifier($expected_user),
      $this->getUserIdentifier($this->createUserIdentifier()
        ->loadUser($identifier)));
  }

  /**
   * Test load user when user is missing.
   *
   * @param mixed $missing_user_identifier
   *   The missing user identifier.
   *
   * @dataProvider missingUserIdentifiers
   */
  public function testLoadUserWhenMissing($missing_user_identifier) {
    $this->assertNull($this->createUserIdentifier()
      ->loadUser($missing_user_identifier));
  }

  /**
   * Get the identifier back from the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return mixed
   *   The identifier.
   */
  protected function getUserIdentifier(UserInterface $user) {
    return call_user_func([$user, $this->identifierMethodName()]);
  }

  /**
   * Creates a user mock.
   *
   * @param mixed $identifier
   *   The identifier.
   *
   * @return \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock of User.
   */
  protected function createUser($identifier): UserInterface {
    $user = $this->createMock(UserInterface::class);
    $user->method($this->identifierMethodName())->willReturn($identifier);
    return $user;
  }

  /**
   * User method to get the value of the identifier back.
   *
   * @return string
   *   User method to get the value of the identifier back.
   */
  abstract protected function identifierMethodName(): string;

  /**
   * Method name to user for user load.
   *
   * @return string
   *   User load method name.
   */
  abstract protected  function storageLoadMethodName(): string;

  /**
   * Creates a user identifier plugin.
   *
   * @return \Drupal\authorization_code\UserIdentifierInterface
   *   User identifier plugin
   */
  abstract protected function createUserIdentifier(): UserIdentifierInterface;

  /**
   * List of valid users.
   *
   * @return array
   *   List of valid users.
   */
  abstract public function validUsers(): array;

  /**
   * Value map for the user storage load method.
   *
   * @return array
   *   Value map for the user storage load method.
   */
  protected function userStorageLoadValueMap(): array {
    return $this->validUsers();
  }

  /**
   * Identifiers for missing useres.
   *
   * @return array
   *   Identifiers for missing useres.
   */
  abstract public function missingUserIdentifiers(): array;

}
