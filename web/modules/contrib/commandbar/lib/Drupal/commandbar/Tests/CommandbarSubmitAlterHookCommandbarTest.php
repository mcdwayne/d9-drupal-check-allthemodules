<?php

/**
 * @file
 * Contains \Drupal\commandbar\Tests\CommandbarSubmitAlterHookCommandbarTest.
 */

namespace Drupal\commandbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_commandbar_submit_alter().
 */
class CommandbarSubmitAlterHookCommandbarTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'commandbar', 'test_page_test', 'commandbar_test');

  public static function getInfo() {
    return array(
      'name' => 'Commandbar hook_commandbar_submit_alter',
      'description' => 'Tests the implementation of hook_commandbar_submit_alter() by a module.',
      'group' => 'Commandbar',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->admin_user = $this->drupalCreateUser(array('access toolbar', 'access commandbar', 'access administration pages'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if we can alter the submit handler via an alter hook.
   */
  function testHookCommandbarSubmitAlter() {

    // Test directing over to the user page instead of the default behavior.
    $edit = array(
      'command' => 'user',
    );
    $this->drupalPost('test-page', $edit, t('Go'));
    $this->assertRaw(t('Member for'));
  }

}