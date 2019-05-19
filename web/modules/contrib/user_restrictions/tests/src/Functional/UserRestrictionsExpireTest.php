<?php

namespace Drupal\Tests\user_restrictions\Functional;

/**
 * Tests rule expirations.
 *
 * @group user_restrictions
 */
class UserRestrictionsExpireTest extends UserRestrictionsTestBase {

  protected $id = 'test_rule_expire_1';

  protected $name = 'Test rule with expiration #1';

  /**
   * Ensure the restriction exists in the database.
   */
  public function testUserRestrictionsRecordExists() {
    $restriction = $this->storage->load($this->id);
    $this->assertNotNull($restriction, 'User Restriction exists in the database');
    $this->assertEqual($restriction->label(), $this->name, 'User restriction exists');
  }

  /**
   * Ensure an expired user may now log in.
   */
  public function testUserRestrictionsExpiredLogin() {
    $account = $this->drupalCreateUser([], 'expired-user');
    $this->drupalLogin($account);
  }

  /**
   * Ensure an expired restriction gets deleted on cron.
   */
  public function testUserRestrictionsExpiredCron() {
    \Drupal::service('cron')->run();
    $this->storage->resetCache();
    $this->assertNull($this->storage->load($this->id), 'User restriction does not exist.');
  }

}
