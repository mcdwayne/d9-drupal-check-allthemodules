<?php

namespace Drupal\js_disclaimer\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the js_disclaimer module.
 *
 * @group js_disclaimer
 */
class JsDisclaimerTests extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['js_disclaimer'];

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
      'administer site configuration',
    ]);
  }

  /**
   * Test if the configuration page exists.
   */
  public function testJsDisclaimerConfigFormExists() {

    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/js-disclaimer');
    $this->assertResponse(200);
  }

  /**
   * Test the configuration form.
   */
  public function testJsDisclaimerConfigForm() {

    $this->drupalLogin($this->user);

    $this->drupalPostForm('admin/config/js-disclaimer', [
      'disclaimer_message' => 'Do you want to leave the website?',
    ], t('Save configuration'));
    $this->assertText(
      'The configuration options have been saved.',
      'The form was saved correctly.'
    );

    $this->drupalGet('admin/config/js-disclaimer');
    $this->assertResponse(200);

    $this->assertFieldByName(
      'disclaimer_message',
      'Do you want to leave the website?',
      'Disclaimer message is OK.'
    );
  }

}
