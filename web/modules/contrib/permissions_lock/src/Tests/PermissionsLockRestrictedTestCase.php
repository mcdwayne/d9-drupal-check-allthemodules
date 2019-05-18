<?php

namespace Drupal\permissions_lock\Tests;

use \Drupal\simpletest\WebTestBase;

/**
 * Tests for restricted users
 *
 * @group permissions_lock
 */
class PermissionsLockRestrictedTestCase extends WebTestBase {

  protected $profile = 'standard';

  public static function getInfo() {
    return [
      'name' => 'Restricted',
      'description' => 'Tests for restricted users',
      'group' => 'Permissions lock',
    ];
  }

  public function setUp() {
    parent::setUp('permissions_lock'); // Enable modules required for the test
    // lock the 'use PHP for settings' permission for testing purposes
    \Drupal::configFactory()->getEditable('permissions_lock.settings')
      ->set('permissions_lock_locked_perm', array_combine([
      'access site in maintenance mode'
    ], ['access site in maintenance mode']))->save();

    // lock the 'authenticated user' role
    \Drupal::configFactory()->getEditable('permissions_lock.settings')->set('permissions_lock_locked_roles', [
      '2' => '2'
    ])->save();

    // Create and log in a user
    $restricted_user = $this->drupalCreateUser([
      'administer permissions'
    ]);
    $this->drupalLogin($restricted_user);
  }

  public function testRestrictedPermission() {
    // go to the permissions administration page
    $this->drupalGet('admin/people/permissions');

    // make sure we are on the administration page
    $this->assertResponse(200, t('User has access to the administration page'));

    // check if the defined permissions are locked
    $this->assertNoText(t('Use the site in maintenance mode'));
  }

  public function testRestrictedRole() {
    // go to the permissions administration page
    $this->drupalGet('admin/people/permissions');

    // make sure we are on the administration page
    $this->assertResponse(200, t('User has access to the administration page'));

    // check if the defined permissions are locked
    $this->assertNoText(t('authenticated user'));
  }

}
