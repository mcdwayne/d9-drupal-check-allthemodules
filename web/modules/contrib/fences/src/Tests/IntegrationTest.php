<?php

/**
 * @file
 * Contains \Drupal\fences\Tests\FencesIntegrationTest
 */

namespace Drupal\fences\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @group fences
 */
class IntegrationTest extends WebTestBase {

  use StripWhitespaceTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'field', 'field_ui', 'fences'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->node = $this->drupalCreateNode(['title' => $this->randomString(), 'type' => 'article', 'body' => 'Body field value.']);
    $this->adminUser = $this->drupalCreateUser(['access content', 'administer node display']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the basic settings.
   */
  public function testBasicSettings() {
    $manage_display = '/admin/structure/types/manage/article/display';
    $this->drupalGet($manage_display);
    $this->drupalPostAjaxForm(NULL, [], 'body_settings_edit');

    $this->drupalPostAjaxForm(NULL, [
      'fields[body][label]' => 'above',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_tag]' => 'article',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_classes]' => 'my-field-class',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_item_tag]' => 'code',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_field_item_classes]' => 'my-field-item-class',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_label_tag]' => 'h2',
      'fields[body][settings_edit_form][third_party_settings][fences][fences_label_classes]' => 'my-label-class',
    ], ['body_plugin_settings_update' => 'Update']);
    $this->drupalPostForm(NULL, [], 'Save');

    $expected_field_markup = <<<EOD
      <article class="my-field-class clearfix text-formatted field field--name-body field--type-text-with-summary field--label-above field__items">
        <h2 class="my-label-class field__label">Body</h2>
        <code class="my-field-item-class field__item">
          <p>Body field value.</p>
        </code>
      </article>
EOD;

    $page = $this->drupalGet('/node/' . $this->node->id());
    $this->assertTrue(strpos($this->stripWhitespace($page), $this->stripWhitespace($expected_field_markup)) !== FALSE, 'Found the correct field markup on the page.');
  }

}
