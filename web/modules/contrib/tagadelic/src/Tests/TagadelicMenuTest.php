<?php

namespace Drupal\tagadelic\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for displaying tagadelic page.
 *
 * @group tagadelic
 */
class TagadelicMenuTest extends WebTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  //public static $modules = array('tagadelic', 'toolbar');
  public static $modules = array('node', 'block', 'menu_ui', 'user', 'taxonomy', 'toolbar', 'tagadelic');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $perms = array(
      'access toolbar',
      'access administration pages',
      'administer site configuration',
      'bypass node access',
      'administer themes',
      'administer nodes',
      'access content overview',
      'administer blocks',
      'administer menu',
      'administer modules',
      'administer permissions',
      'administer users',
      'access user profiles',
      'administer taxonomy',
    );

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser($perms);

    $this->drupalLogin($this->adminUser);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');
  }

  /**
   * Test page displays.
   */
  function testTagadelicMenusExist() {
    $this->drupalGet('admin/structure');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/structure/tagadelic');
  }
}
