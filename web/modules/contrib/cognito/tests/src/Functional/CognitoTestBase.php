<?php

namespace Drupal\Tests\cognito\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for functional tests.
 */
abstract class CognitoTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'cognito',
    'cognito_tests',
  ];

  /**
   * Creates an external user.
   *
   * @param array $permissions
   *   The permissions for the admin user.
   * @param array $extraFields
   *   The extra user fields.
   *
   * @return \Drupal\user\UserInterface
   *   The newly created user.
   */
  protected function createExternalUser(array $permissions = [], array $extraFields = []) {
    $role = $this->createRole($permissions);

    $mail = strtolower($this->randomMachineName() . '@example.com');
    return \Drupal::service('externalauth.externalauth')
      ->register($mail, 'cognito', [
        'name' => $mail,
        'mail' => $mail,
        'roles' => [$role],
      ] + $extraFields);
  }

}
