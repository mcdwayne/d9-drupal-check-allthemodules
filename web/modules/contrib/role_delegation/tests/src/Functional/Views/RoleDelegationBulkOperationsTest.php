<?php

namespace Drupal\Tests\role_delegation\Functional\Views;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for assigning roles in vbo.
 *
 * @group role_delegation
 */
class RoleDelegationBulkOperationsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['action', 'user', 'role_delegation', 'views'];

  /**
   * Test if a user is able to edit the allowed roles in VBO.
   */
  public function testVboRoleDelegation() {
    $rid1 = $this->drupalCreateRole([]);
    $rid2 = $this->drupalCreateRole([]);
    $rid3 = $this->drupalCreateRole([]);

    // User that can assign all roles.
    $account = $this->createUser(['administer users', 'assign all roles']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/people');
    $this->assertOption('action', sprintf('user_add_role_action.%s', $rid1));
    $this->assertOption('action', sprintf('user_add_role_action.%s', $rid2));
    $this->assertOption('action', sprintf('user_add_role_action.%s', $rid3));
    $this->assertOption('action', sprintf('user_remove_role_action.%s', $rid1));
    $this->assertOption('action', sprintf('user_remove_role_action.%s', $rid2));
    $this->assertOption('action', sprintf('user_remove_role_action.%s', $rid3));

    // User that can assign only role 1.
    $account = $this->createUser([
      'administer users',
      sprintf('assign %s role', $rid1),
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/people');
    $this->assertOption('action', sprintf('user_add_role_action.%s', $rid1));
    $this->assertNoOption('action', sprintf('user_add_role_action.%s', $rid2));
    $this->assertNoOption('action', sprintf('user_add_role_action.%s', $rid3));
    $this->assertOption('action', sprintf('user_remove_role_action.%s', $rid1));
    $this->assertNoOption('action', sprintf('user_remove_role_action.%s', $rid2));
    $this->assertNoOption('action', sprintf('user_remove_role_action.%s', $rid3));

    // User that can assign role 2 and role 3.
    $account = $this->createUser([
      'administer users',
      sprintf('assign %s role', $rid2),
      sprintf('assign %s role', $rid3),
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/people');
    $this->assertNoOption('action', sprintf('user_add_role_action.%s', $rid1));
    $this->assertOption('action', sprintf('user_add_role_action.%s', $rid2));
    $this->assertOption('action', sprintf('user_add_role_action.%s', $rid3));
    $this->assertNoOption('action', sprintf('user_remove_role_action.%s', $rid1));
    $this->assertOption('action', sprintf('user_remove_role_action.%s', $rid2));
    $this->assertOption('action', sprintf('user_remove_role_action.%s', $rid3));
  }

}
