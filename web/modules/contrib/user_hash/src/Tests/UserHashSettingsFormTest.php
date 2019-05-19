<?php

namespace Drupal\user_hash\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user hash settings form.
 *
 * @group user_hash
 *
 * @requires user
 */
class UserHashSettingsFormTest extends WebTestBase {

  /**
   * A user with permission to administrate user hashes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'user_hash'];

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
    ]);
  }

  /**
   * Test user hash settings form.
   */
  public function testUserHashSettingsForm() {
    $this->drupalLogin($this->adminUser);

    $edit = [
      'algorithm' => 'sha1',
      'random_bytes' => 48,
    ];
    $path = 'admin/config/people/user_hash';
    $this->drupalPostForm($path, $edit, t('Save configuration'));
    $this->drupalGet($path);
    $this->assertResponse(200);
    $this->assertFieldByName('algorithm', 'sha1', 'Hash algorithm configuration saved.');
    $this->assertFieldByName('random_bytes', '48', 'Random bytes configuration saved.');
  }

}
