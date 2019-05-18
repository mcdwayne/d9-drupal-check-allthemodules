<?php

namespace Drupal\opencalais_ui\Tests;

use Drupal\node\Entity\Node;

/**
 * Tests the Open Calais UI admin.
 *
 * @group opencalais_ui
 */
class OpenCalaisUiAdminTest extends OpenCalaisUiTestBase {

  /**
   * Tests Open Calais UI configuration form.
   */
  public function testConfigForm() {
    $this->loginAsAdmin();
    // Create a test node.
    $node = Node::create([
      'title' => 'Target node',
      'type' => 'article',
      'body' => 'Target body text',
    ]);
    $node->save();

    // Assert that the API key is not set and the message is displayed.
    $this->drupalGet('admin/config/content/opencalais/general');
    $this->assertFieldByName('api_key', '');
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertLink('here');
    $this->assertText('No API key has been set. Click here to set it');
    $this->assertNoText('No Open Calais field has been set. Click here to set it');

    // Set the API key and check that the message is no longer displayed.
    $this->setTestApiKey();
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertLink('here');
    $this->assertNoText('No API key has been set. Click here to set it');
    // Assert the message of the missing open calais field.
    $this->assertText('No Open Calais field has been set. Click here to set it');

    // Add a taxonomy field and check if the message is no longer displayed.
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertText('The content type has no taxonomy fields available. Please add one to use Open Calais.');
    $field_edit = [
      'settings[handler_settings][target_bundles][entities]' => TRUE,
      'settings[handler_settings][target_bundles][industry_tags]' => TRUE,
      'settings[handler_settings][target_bundles][markup_tags]' => TRUE,
      'settings[handler_settings][target_bundles][social_tags]' => TRUE,
      'settings[handler_settings][target_bundles][topic_tags]' => TRUE
    ];
    static::fieldUIAddNewField('admin/structure/types/manage/article', 'taxonomy_test', 'taxonomy_test', 'field_ui:entity_reference:taxonomy_term', [], $field_edit);
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertNoText('The content type has no taxonomy fields available. Please add one to use Open Calais.');

    // Set the open calais field and check if the message is no longer displayed.
    $this->setTestOpenCalaisField('field_taxonomy_test');
    $this->drupalGet('node/' . $node->id() . '/opencalais_tags');
    $this->assertNoText('No API key has been set. Click here to set it');
    $this->assertNoText('No Open Calais field has been set. Click here to set it');
  }

}
