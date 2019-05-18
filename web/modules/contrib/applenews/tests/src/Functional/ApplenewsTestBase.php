<?php

namespace Drupal\Tests\applenews\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Setup users and configurations.
 */
abstract class ApplenewsTestBase extends BrowserTestBase {
  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A normal user with permission to bypass node access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['applenews', 'serialization', 'block', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer applenews configuration',
      'administer applenews templates',
      'administer applenews channels',
    ]);
    $this->baseUser = $this->drupalCreateUser([]);

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

}
