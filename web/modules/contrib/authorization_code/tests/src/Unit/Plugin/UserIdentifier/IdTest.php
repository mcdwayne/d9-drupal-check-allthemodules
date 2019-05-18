<?php

namespace Drupal\Tests\authorization_code\Unit\Plugin\UserIdentifier;

use Drupal\authorization_code\Plugin\UserIdentifier\UserId;
use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Tests\authorization_code\Unit\Plugin\UserIdentifierTestBase;

/**
 * Id based user identifier unit test.
 *
 * @group authorization_code
 */
class IdTest extends UserIdentifierTestBase {

  /**
   * {@inheritdoc}
   */
  protected function identifierMethodName(): string {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  protected function storageLoadMethodName(): string {
    return 'load';
  }

  /**
   * {@inheritdoc}
   */
  protected function createUserIdentifier(): UserIdentifierInterface {
    return UserId::create($this->container, ['plugin_id' => 'user_id'], 'user_id', []);
  }

  /**
   * {@inheritdoc}
   */
  public function validUsers(): array {
    return array_map(function ($id) {
      return [$id, $this->createUser($id)];
    }, range(1, static::TEST_USERS_COUNT));
  }

  /**
   * {@inheritdoc}
   */
  public function missingUserIdentifiers(): array {
    return array_map(function () {
      return [mt_rand(static::TEST_USERS_COUNT + 1, 99)];
    }, range(1, 30));
  }

}
