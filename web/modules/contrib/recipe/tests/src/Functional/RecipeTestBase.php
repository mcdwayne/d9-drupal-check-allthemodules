<?php

namespace Drupal\Tests\recipe\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides setup and helper methods for recipe module tests.
 */
abstract class RecipeTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'recipe', 'views'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create and log in the admin user with Recipe content permissions.
    $permissions = [
      'create recipe content',
      'edit any recipe content',
      'administer site configuration',
      'view ingredient'
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

}
