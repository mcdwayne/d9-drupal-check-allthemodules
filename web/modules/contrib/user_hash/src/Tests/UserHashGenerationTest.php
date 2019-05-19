<?php

namespace Drupal\user_hash\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user hash generation.
 *
 * @group user_hash
 *
 * @requires user
 */
class UserHashGenerationTest extends WebTestBase {

  /**
   * A user with permission to administrate user hashes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with the 'access user profiles' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser1;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'views', 'user', 'user_hash'];

  /**
   * Set up test environment.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer users',
      'administer account settings',
      'access user profiles',
      'use user_hash',
    ]);
    $this->baseUser1 = $this->drupalCreateUser([
      'access user profiles',
    ]);
  }

  /**
   * Test user hash generation.
   */
  public function testUserHashGeneration() {
    $this->drupalLogin($this->adminUser);

    $edit = [
      'action' => 'user_generate_user_hash_action',
      'user_bulk_form[1]' => TRUE,
    ];
    $path = 'admin/people';
    $this->drupalPostForm($path, $edit, t('Apply to selected items'), [], [], 'views-form-user-admin-people-page-1');
    $this->assertText('Generate hash for the selected user(s) was applied to', 'Hash generated.');
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertResponse(200);
    $this->assertPattern('/Hash<\/h4> [0-9A-Fa-f]{64}/', 'User hash displayed on profile.');

    $edit = [
      'action' => 'user_generate_user_hash_action',
      'user_bulk_form[0]' => TRUE,
    ];
    $path = 'admin/people';
    $this->drupalPostForm($path, $edit, t('Apply to selected items'), [], [], 'views-form-user-admin-people-page-1');
    $this->assertText('No access to execute Generate hash for the selected user(s) on the User', 'Hash not generated.');

    $this->drupalLogout();
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertResponse(200);
    $this->assertNoPattern('/Hash<\/h4> [0-9A-Fa-f]{64}/', 'User hash not displayed on profile.');

    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $edit = [
      'action' => 'user_delete_user_hash_action',
      'user_bulk_form[1]' => TRUE,
    ];
    $path = 'admin/people';
    $this->drupalPostForm($path, $edit, t('Apply to selected items'), [], [], 'views-form-user-admin-people-page-1');
    $this->assertText('Delete hash from the selected user(s) was applied to', 'Hash deleted.');
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertResponse(200);
    $this->assertNoPattern('/Hash<\/h4>/', 'User hash not displayed on profile.');
  }

}
