<?php

/**
 * @file
 * Definition of Drupal\commandbar\Tests\CommandbarAutocompleteTest.
 */

namespace Drupal\commandbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test commandbar autocompletion.
 */
class CommandbarAutocompleteTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'commandbar', 'test_page_test');

  public static function getInfo() {
    return array(
      'name' => 'Commandbar autocompletion',
      'description' => 'Test commandbar autocompletion functionality.',
      'group' => 'Commandbar'
    );
  }

  function setUp() {
    parent::setUp();

    // Set up two users with different permissions to test access.
    $this->unprivileged_user = $this->drupalCreateUser();
    $this->privileged_user = $this->drupalCreateUser(array('access toolbar', 'access commandbar', 'access administration pages', 'administer modules'));
  }

  /**
   * Tests access to commandbar autocompletion and verify the correct results.
   */
  function testCommandbarAutocomplete() {

    // Check access from unprivileged user, should be denied.
    $this->drupalLogin($this->unprivileged_user);
    $this->drupalGet('commandbar/autocomplete', array('query' => array('q' => 'mod')));
    $this->assertResponse(403, 'Autocompletion access denied to user without permission.');

    // Check access from privileged user.
    $this->drupalLogout();
    $this->drupalLogin($this->privileged_user);

    // Check existence of commandbar form.
    $this->drupalGet('test-page');
    $this->assertRaw('class="commandbar-bar-form"', 'Commandbar form exists.');

    $this->drupalGet('commandbar/autocomplete', array('query' => array('q' => 'mod')));
    $this->assertResponse(200, 'Autocompletion access allowed.');

    // Test by using 'mod' to see if the 'Extend' result shows up.
    $this->assertRaw('Extend', 'Extend found in autocompletion results when searching for "mod".');

  }
}