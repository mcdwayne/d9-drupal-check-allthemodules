<?php

namespace Drupal\Tests\debugme\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test roles functionality of DebugMe module.
 *
 * @group debugme
 */
class DebugMeVisibility extends BrowserTestBase {

  /**
   * A test user with normal privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['debugme', 'user', 'node', 'views'];

  /**
   * Show DebugMe on every page except the listed pages.
   */
  const EXCLUDED_PAGES = 0;

  /**
   * Show DebugMe on the listed pages only.
   */
  const LISTED_PAGES_ONLY = 1;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = ['use debugme'];
    $this->user = $this->drupalCreateUser($permissions);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->config('system.site')->set('page.front', '/node')->save();
  }

  /**
   * Tests if roles based tracking works.
   */
  public function testDebugMeVisibility() {
    // Array keyed list where key being the URL address and value being expected
    // visibility as boolean type.
    $paths = [
      '/node' => FALSE,
      '/user' => TRUE,
    ];

    $this->config('debugme.settings')->set('visibility.request_path_mode', self::EXCLUDED_PAGES)->save();
    $this->config('debugme.settings')->set('visibility.request_path_pages', "/node")->save();

    // DebugMe should not be visible if project id is not set.
    foreach ($paths as $path => $expected_visibility) {
      $this->drupalGet($path);
      $this->assertSession()->responseNotContains('dbg.src = "https://debugme.eu/App.js";');
    }

    $this->config('debugme.settings')->set('project', '123456abcde')->save();

    // DebugMe should not be visible if user does not have permissions.
    foreach ($paths as $path => $expected_visibility) {
      $this->drupalGet($path);
      $this->assertSession()->responseNotContains('dbg.src = "https://debugme.eu/App.js";');
    }

    // DebugMe should be visible only on selected pages.
    $this->drupalLogin($this->user);
    foreach ($paths as $path => $expected_visibility) {
      $this->drupalGet($path);

      if ($expected_visibility) {
        $this->assertSession()->responseContains('dbg.src = "https://debugme.eu/App.js";');
      }
      else {
        $this->assertSession()->responseNotContains('dbg.src = "https://debugme.eu/App.js";');
      }
    }
  }

}
