<?php

namespace Drupal\Tests\authorization_code\Unit\Plugin\UserIdentifier;

use Drupal\authorization_code\Plugin\UserIdentifier\Username;
use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Tests\authorization_code\Unit\Plugin\UserIdentifierTestBase;

/**
 * Username based user identifier unit test.
 *
 * @group authorization_code
 */
class UsernameTest extends UserIdentifierTestBase {

  /**
   * {@inheritdoc}
   */
  protected function identifierMethodName(): string {
    return 'getAccountName';
  }

  /**
   * {@inheritdoc}
   */
  protected function storageLoadMethodName(): string {
    return 'loadByProperties';
  }

  /**
   * {@inheritdoc}
   */
  protected function createUserIdentifier(): UserIdentifierInterface {
    return Username::create($this->container, ['plugin_id' => 'username'], 'username', []);
  }

  /**
   * {@inheritdoc}
   */
  public function validUsers(): array {
    return array_map(function ($email) {
      return [$email, $this->createUser($email)];
    }, ['foo', 'bar', 'quo']);
  }

  /**
   * {@inheritdoc}
   */
  protected function userStorageLoadValueMap(): array {
    return array_map(function ($pair) {
      $pair = array_merge($pair, [NULL]);
      return [['name' => $pair[0]], array_filter([$pair[1]])];
    }, array_merge($this->validUsers(), $this->missingUserIdentifiers()));
  }

  /**
   * {@inheritdoc}
   */
  public function missingUserIdentifiers(): array {
    return [['foz'], ['baz'], ['quz']];
  }

}
