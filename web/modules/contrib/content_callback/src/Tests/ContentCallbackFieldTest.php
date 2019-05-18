<?php

/**
 * @file
 * Contains \Drupal\content_callback\ContentCallbackFieldTest.
 */

namespace Drupal\content_callback\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the creation of content callback fields.
 *
 * @group content_callback
 */
class ContentCallbackFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'node',
    'options',
    'number',
    'content_callback',
    'content_callback_test',
  );

  protected $web_user;

  public static function getInfo() {
    return array(
      'name'  => 'Content callback field',
      'description'  => "Test the creation of content callback fields.",
      'group' => 'Field types'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    $this->article_creator = $this->drupalCreateUser(array('create article content', 'edit own article content'));
    $this->drupalLogin($this->article_creator);
  }

  /**
   * Tests the content callback field.
   */
  function testContentCallbackField() {

    // Add the telepone field to the article content type.
    entity_create('field_entity', array(
      'name' => 'field_content_callback',
      'entity_type' => 'node',
      'type' => 'content_callback',
    ))->save();
    entity_create('field_instance', array(
      'field_name' => 'field_content_callback',
      'label' => 'Content callback',
      'entity_type' => 'node',
      'bundle' => 'article',
    ))->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_content_callback', array(
        'type' => 'content_callback_select',
      ))
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent('field_content_callback', array(
        'type' => 'content_callback_default',
        'weight' => 1,
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_content_callback[select]", '', 'Widget found.');
    $this->assertRaw('- Select a value -');

    // Test basic entery of telephone field.
    $edit = array(
      "title" => $this->randomName(),
      "field_content_callback[select]" => 'content_callback_test',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw('this is a test content callback');
  }
}
