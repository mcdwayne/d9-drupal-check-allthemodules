<?php

namespace Drupal\shell\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Basic tests for Shell.
 *
 * @group Shell
 */
class ShellTest extends WebTestBase {

  public static $modules = ['shell'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a user and log it in.
    $this->adminUser = $this->drupalCreateUser([
      'execute shell commands',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test functionality of the module.
   */
  public function testTextimage() {
    $this->drupalGet('shell/form');
    $this->assertText('Welcome to Shell.');
  }

}
