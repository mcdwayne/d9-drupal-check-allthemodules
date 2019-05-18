<?php

namespace Drupal\Tests\authorization_code\Unit\Plugin\UserIdentifier;

use Drupal\authorization_code\Plugin\UserIdentifier\Email;
use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Tests\authorization_code\Unit\Plugin\UserIdentifierTestBase;

/**
 * Email user identifier unit test.
 *
 * @group authorization_code
 */
class EmailTest extends UserIdentifierTestBase {

  /**
   * {@inheritdoc}
   */
  protected function identifierMethodName(): string {
    return 'getEmail';
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
    return Email::create($this->container, ['plugin_id' => 'email'], 'email', []);
  }

  /**
   * {@inheritdoc}
   */
  public function validUsers(): array {
    return array_map(function ($email) {
      return [$email, $this->createUser($email)];
    }, ['foo@example.com', 'bar@example.com', 'quo@example.com']);
  }

  /**
   * {@inheritdoc}
   */
  protected function userStorageLoadValueMap(): array {
    return array_map(function ($pair) {
      $pair = array_merge($pair, [NULL]);
      return [['mail' => $pair[0]], array_filter([$pair[1]])];
    }, array_merge($this->validUsers(), $this->missingUserIdentifiers()));
  }

  /**
   * {@inheritdoc}
   */
  public function missingUserIdentifiers(): array {
    return [['foz@example.com'], ['baz@example.com'], ['quz@example.com']];
  }

}
