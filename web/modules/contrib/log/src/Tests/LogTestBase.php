<?php

namespace Drupal\log\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Log CRUD.
 */
abstract class LogTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @see \Drupal\simpletest\WebTestBase::installModulesFromClassProperty()
   *
   * @var array
   */
  public static $modules = [
    'user', 'log',
    'log_test',
    'field', 'text',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $restrictedUser;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unauthorizedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->unauthorizedUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->restrictedUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
    drupal_flush_all_caches();
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getUnauthorizedPermissions() {
    return [
      'view any default log entities',
    ];
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getRestrictedPermissions() {
    return [
      'access administration pages',
      'administer logs',
      'create default log entities',
      'view own default log entities',
      'edit own default log entities',
      'delete own default log entities',
    ];
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'access administration pages',
      'administer logs',
      'administer log module',
      'create default log entities',
      'view any default log entities',
      'edit any default log entities',
      'delete any default log entities',
      'view default revisions',
      'revert default revisions',
      'delete default revisions',
    ];
  }

  /**
   * @param array $values
   *
   * @return \Drupal\log\LogInterface
   */
  protected function createLogEntity($values = []) {
    $storage = \Drupal::service('entity_type.manager')->getStorage('log');
    $entity = $storage->create($values + [
      'name' => $this->randomMachineName(),
      'user_id' => $this->loggedInUser->id(),
      'created' => REQUEST_TIME,
      'type' => 'default',
      'done' => TRUE,
    ]);
    return $entity;
  }
}