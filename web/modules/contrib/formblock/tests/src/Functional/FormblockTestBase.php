<?php

namespace Drupal\Tests\formblock\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines the common form block test code.
 */
abstract class FormblockTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'contact', 'user', 'formblock'];

  /**
   * A user with permission to administer blocks.
   *
   * @var \Drupal\user\UserInterface
   */
  public $adminUser;


  protected function setUp() {
    parent::setUp();

    // Log in as a user that can administer bocks.
    $this->adminUser = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($this->adminUser);
  }

}
