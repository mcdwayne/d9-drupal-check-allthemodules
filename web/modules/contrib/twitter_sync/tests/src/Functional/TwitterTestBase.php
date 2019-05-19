<?php

namespace Drupal\Tests\twitter_sync\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Twitter Unit test class.
 */
abstract class TwitterTestBase extends BrowserTestBase {

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer site configuration',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twitter_sync'];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_actions_block');
  }

}
