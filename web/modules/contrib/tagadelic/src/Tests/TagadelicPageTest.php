<?php

namespace Drupal\tagadelic\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for displaying tagadelic page.
 *
 * @group tagadelic
 */
class TagadelicPageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tagadelic', 'taxonomy');

  /**
   * Test page displays.
   */
  function testTagadelicPageExists() {
    $user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($user);
    $this->drupalGet('tagadelic');
    $this->assertResponse(200);
    $this->assertRaw('Tag Cloud');
  }
}
