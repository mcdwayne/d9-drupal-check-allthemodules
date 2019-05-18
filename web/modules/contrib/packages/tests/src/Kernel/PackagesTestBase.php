<?php

namespace Drupal\Tests\packages\Kernel;

use Drupal\user\Entity\User;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for packages kernel tests.
 */
abstract class PackagesTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'packages',
    'packages_test',
  ];

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->packages = \Drupal::service('packages');
  }

  /**
   * Create a user.
   *
   * @param int $role_id
   *   An optional role ID to assign to the user.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  public function createUser($role_id = NULL) {
    $args = [];
    $args['name'] = $this->randomMachineName();
    if ($role_id) {
      $args['roles'] = [$role_id];
    }
    $user = User::create($args);
    $user->save();
    return $user;
  }

  /**
   * Mimic logging a user in by setting the account as the current user.
   *
   * @param \Drupal\user\Entity\User $account
   *   A user entity.
   */
  public function logIn(User $account) {
    \Drupal::currentUser()->setAccount($account);
  }

}
