<?php

namespace Drupal\Tests\role_expire\Functional;

/**
 * Tests that the Role expire API works.
 *
 * @code
 * vendor/bin/phpunit ../modules/role_expire/tests/src/Functional/RoleExpireApiTest.php
 * @endcode
 *
 * @group role_expire
 *
 * Remember: each test run on a separate Drupal instance.
 *
 * https://api.drupal.org/api/drupal/core%21tests%21Drupal%21Tests%21BrowserTestBase.php/class/BrowserTestBase/8.6.x
 */
class RoleExpireApiTest extends RoleExpireBrowserTest {

  /**
   * Role expire API service.
   *
   * @var \Drupal\role_expire\RoleExpireApiService
   */
  protected $apiService;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['role_expire'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->apiService = $this->container->get('role_expire.api');
  }

  /**
   * Tests setter and getter for expiration of a role.
   */
  public function testRoleExpireSetGet() {
    $account = $this->drupalCreateUser(['administer role expire']);
    $this->drupalLogin($account);
    $account_id = $account->id();

    // New users have no expiration time.
    $saved_expiration = $this->apiService->getUserRoleExpiryTime($account_id, 'administrator');
    $this->assertEqual('', $saved_expiration);

    // Check expiration date saving process.
    $expiration = strtotime('+1 year');
    $saved_expiration = $this->setAndGetExpiration($account_id, 'administrator', $expiration);
    $this->assertEqual($expiration, $saved_expiration);

    $expiration = strtotime('+1 month');
    $saved_expiration = $this->setAndGetExpiration($account_id, 'administrator', $expiration);
    $this->assertEqual($expiration, $saved_expiration);

    $expiration = strtotime('12 hours');
    $saved_expiration = $this->setAndGetExpiration($account_id, 'administrator', $expiration);
    $this->assertEqual($expiration, $saved_expiration);
  }

  /**
   * Tests setter and getter for default durations of a role.
   */
  public function testRoleExpireDefaultDurationsSetGet() {
    $account = $this->drupalCreateUser(['administer role expire']);
    $this->drupalLogin($account);
    $account_id = $account->id();

    // Initially roles have no default duration time.
    $saved_duration = $this->apiService->getDefaultDuration('administrator');
    $this->assertEqual('', $saved_duration);

    // Check default duration saving process.
    $default_duration = 'last day of month';
    $this->apiService->setDefaultDuration('administrator', $default_duration);
    $saved_duration = $this->apiService->getDefaultDuration('administrator');
    $this->assertEqual($default_duration, $saved_duration);
  }

  /**
   * Tests getAllUserRecords and three delete methods.
   */
  public function testRoleExpireGetAllUserRecordsAndDeletes() {
    $account = $this->createUser(['administer role expire']);
    $this->drupalLogin($account);
    $account_id = $account->id();

    $rid_1 = 'role_test_1';
    $rid_2 = 'role_test_2';
    $rid_3 = 'role_test_3';

    $this->createRole([], $rid_1, 'Role test 1');
    $this->createRole([], $rid_2, 'Role test 2');
    $this->createRole([], $rid_3, 'Role test 3');

    $account->addRole($rid_1);
    $account->addRole($rid_2);
    $account->addRole($rid_3);

    $expiration_1 = strtotime('+1 day');
    $expiration_2 = strtotime('+3 months');
    $expiration_3 = strtotime('+1 year');
    $this->apiService->writeRecord($account_id, $rid_1, $expiration_1);
    $this->apiService->writeRecord($account_id, $rid_2, $expiration_2);
    $this->apiService->writeRecord($account_id, $rid_3, $expiration_3);

    // Test getAllUserRecords method.
    $results = $this->apiService->getAllUserRecords($account_id);
    $actual = (count($results) == 3);
    $this->assertTrue($actual);

    // Test delete method 1.
    $this->apiService->deleteRecord($account_id, $rid_1);
    $results = $this->apiService->getAllUserRecords($account_id);
    $actual = (count($results) == 2);
    $this->assertTrue($actual);

    // Test delete method 2.
    $this->apiService->deleteRoleRecords($rid_2);
    $results = $this->apiService->getAllUserRecords($account_id);
    $actual = (count($results) == 1);
    $this->assertTrue($actual);

    // Test delete method 3.
    $this->apiService->deleteUserRecords($account_id);
    $results = $this->apiService->getAllUserRecords($account_id);
    $actual = (count($results) == 0);
    $this->assertTrue($actual);
  }

  /**
   * Tests getExpired method.
   */
  public function testRoleExpireGetExpired() {
    $account = $this->createUser(['administer role expire']);
    $this->drupalLogin($account);
    $account_id = $account->id();

    $rid_1 = 'role_test_1';
    $rid_2 = 'role_test_2';

    $this->createRole([], $rid_1, 'Role test 1');
    $this->createRole([], $rid_2, 'Role test 2');

    $account->addRole($rid_1);
    $account->addRole($rid_2);

    $expiration_1 = strtotime('+1 day');
    $expiration_2 = strtotime('+2 days');
    $this->apiService->writeRecord($account_id, $rid_1, $expiration_1);
    $this->apiService->writeRecord($account_id, $rid_2, $expiration_2);

    $fake_current_time = strtotime('+1 month');
    $results = $this->apiService->getExpired($fake_current_time);
    $actual = (count($results) == 2);
    $this->assertTrue($actual);

    $results = $this->apiService->getExpired();
    $actual = (count($results) == 0);
    $this->assertTrue($actual);
  }

}
