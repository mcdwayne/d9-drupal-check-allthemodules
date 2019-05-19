<?php

namespace Drupal\Tests\user_restrictions\Functional;

use Drupal\Tests\BrowserTestBase;

abstract class UserRestrictionsTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_restrictions', 'user_restrictions_test'];

  /**
   * The restriction storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = \Drupal::service('entity_type.manager')
      ->getStorage('user_restrictions');

    // Allow registration by site visitors without administrator approval.
    \Drupal::configFactory()->getEditable('user.settings')->set('register', USER_REGISTER_VISITORS)->save();
  }

}
