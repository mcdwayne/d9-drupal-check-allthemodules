<?php

namespace Drupal\ckeditor_content_style\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the ckeditor content style module.
 *
 * @group ckeditor_content_style
 */
class CkcsTests extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['ckeditor_content_style', 'ckeditor', 'editor'];

  /**
   * A simple user.
   *
   * @var object
   */
  private $user;

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->DrupalCreateUser([
      'access administration pages',
    ]);
  }

  /**
   * Tests that the ckcs admin page can be reached.
   */
  public function testckcsPageExists() {
    // Login.
    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('admin/ckcs');
    $this->assertResponse(200);

    // Access add new entity page.
    $this->drupalGet('admin/ckcs/form/add');
    $this->assertResponse(200);
  }

}
