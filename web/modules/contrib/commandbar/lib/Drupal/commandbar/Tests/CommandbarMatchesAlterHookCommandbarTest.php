<?php

/**
 * @file
 * Contains \Drupal\commandbar\Tests\CommandbarMatchesAlterHookCommandbarTest.
 */

namespace Drupal\commandbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests hook_commandbar_matches_alter().
 */
class CommandbarMatchesAlterHookCommandbarTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'commandbar', 'test_page_test', 'commandbar_test');

  public static function getInfo() {
    return array(
      'name' => 'Commandbar hook_commandbar_matches_alter',
      'description' => 'Tests the implementation of hook_commandbar_matches_alter() by a module.',
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
   * Tests to see if we can modify the matches for the autocomplete input.
   */
  function testHookCommandbarMatchesAlter() {

    // Assert that the matches have been altered.
    $this->drupalGet('commandbar/autocomplete', array('query' => array('q' => 'commandbar_match_test')));
    $this->assertRaw('commandbar_match_test_result', 'The function hook_commandbar_matches_alter altered the results.');
  }

}