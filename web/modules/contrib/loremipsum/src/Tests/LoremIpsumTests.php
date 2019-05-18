<?php

namespace Drupal\loremipsum\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the Lorem Ipsum module.
 * @group loremipsum
 */
class LoremIpsumTests extends WebTestBase {

  /**
   * Modules to install
   *
   * @var array
   */
  public static $modules = array('loremipsum');

  // A simple user
  private $user;

  // Perform initial setup tasks that run before every test method.
  public function setUp() {
    parent::setUp();
    $this->user = $this->DrupalCreateUser(array(
      'administer site configuration',
      'generate lorem ipsum',
    ));
  }

  /**
   * Tests that the Lorem ipsum page can be reached.
   * TODO: block test
   */
  public function testLoremIpsumPageExists() {
    // Login
    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('loremipsum/generate/4/20');
    $this->assertResponse(200);
  }

  /**
   * Tests the config form.
   */
  public function testConfigForm() {
    // Login
    $this->drupalLogin($this->user);

    // Access config page
    $this->drupalGet('admin/config/development/loremipsum');
    $this->assertResponse(200);
    // Test the form elements exist and have defaults
    $config = $this->config('loremipsum.settings');
    $this->assertFieldByName(
      'page_title',
      $config->get('loremipsum.settings.page_title'),
      'Page title field has the default value'
    );
    $this->assertFieldByName(
      'source_text',
      $config->get('loremipsum.settings.source_text'),
      'Source text field has the default value'
    );
    // Test form submission
    $this->drupalPostForm(NULL, array(
      'page_title' => 'Test lorem ipsum',
      'source_text' => 'Test phrase 1 \nTest phrase 2 \nTest phrase 3 \n',
    ), t('Save configuration'));
    $this->assertText(
      'The configuration options have been saved.',
      'The form was saved correctly.'
    );
    // Test the new values are there.
    $this->drupalGet('admin/config/development/loremipsum');
    $this->assertResponse(200);
    $this->assertFieldByName(
      'page_title',
      'Test lorem ipsum',
      'Page title is OK.'
    );
    $this->assertFieldByName(
      'source_text',
      'Test phrase 1 \nTest phrase 2 \nTest phrase 3 \n',
      'Source text is OK.'
    );
  }

}