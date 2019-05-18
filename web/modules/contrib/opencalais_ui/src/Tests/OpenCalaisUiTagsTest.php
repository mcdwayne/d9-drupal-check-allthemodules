<?php

namespace Drupal\opencalais_ui\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\Node;

/**
 * Tests the Open Calais UI tags.
 *
 * @group opencalais_ui
 */
class OpenCalaisUiTagsTest extends OpenCalaisUiTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'comment'
  ];

  /**
   * Tests Open Calais UI tags form.
   */
  public function testTagsForm() {
    // Add a comment field to the content type.
    $this->loginAsAdmin([
      'access comments',
      'post comments',
    ]);
    $this->addDefaultCommentField('node', 'article', 'field_comment_test');
    $field_edit = [
      'settings[handler_settings][target_bundles][entities]' => TRUE,
      'settings[handler_settings][target_bundles][industry_tags]' => TRUE,
      'settings[handler_settings][target_bundles][markup_tags]' => TRUE,
      'settings[handler_settings][target_bundles][social_tags]' => TRUE,
      'settings[handler_settings][target_bundles][topic_tags]' => TRUE
    ];
    static::fieldUIAddNewField('admin/structure/types/manage/article', 'taxonomy_test', 'taxonomy_test', 'field_ui:entity_reference:taxonomy_term', [], $field_edit);
    $this->setTestApiKey();
    $this->setTestOpenCalaisField('field_taxonomy_test');

    // Create a test node.
    $node = Node::create([
      'title' => 'Test node',
      'type' => 'article',
      'body' => 'recognizable_text',
    ]);
    $node->save();

    // Assert the form is displayed when there are comment fields.
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertResponse(200);
    $this->assertText('recognizable_text');
  }

}
