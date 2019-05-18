<?php

/**
 * @file
 * Contains \Drupal\commandbar\Tests\CommandbarBuildAlterHookCommandbarTest.
 */

namespace Drupal\commandbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_commandbar_build_alter().
 */
class CommandbarBuildAlterHookCommandbarTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'commandbar', 'test_page_test', 'commandbar_test');

  public static function getInfo() {
    return array(
      'name' => 'Commandbar hook_commandbar_build_alter',
      'description' => 'Tests the implementation of hook_commandbar_build_alter() by a module.',
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
   * Tests to see if we can take over the creation of the autocomplete result set.
   */
  function testHookCommandbarBuildAlter() {
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    $this->drupalGet('commandbar/autocomplete', array('query' => array('q' => 'commandbar_test')));

    // Assert that the we were able to alter the source of returned matches.
    $this->assertRaw('commandbar_test_result', 'The function hook_commandbar_build_alter altered the results.');
    $test = '';
  }

}