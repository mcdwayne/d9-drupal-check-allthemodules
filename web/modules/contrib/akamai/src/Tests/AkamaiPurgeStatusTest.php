<?php

namespace Drupal\akamai\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the Purge Status page.
 *
 * @group Akamai
 */
class AkamaiPurgeStatusTest extends WebTestBase {

  /**
   * User with admin rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test', 'node', 'user', 'akamai'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser([
      'purge akamai cache',
      'administer akamai',
      'purge akamai cache',
    ]);
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Tests that Akamai Purge Status page.
   */
  public function testPurgeStatusPageGracefullyFailsWhenUnauthenticated() {
    $this->drupalGet('admin/config/akamai/list');
    $this->assertText(t('Missing valid authentication credentials.'));
  }

}
