<?php

namespace Drupal\field_tokens\Tests;

use Drupal\node\Entity\Node;

/**
 * Tests general functionality.
 *
 * @group Field tokens
 */
class FieldTokensGeneralTest extends FieldTokensTestBase {

  /**
   * Test hidden fields with a Field tokens rendered field.
   *
   * @see http://drupal.org/node/2543548
   */
  public function testHiddenFields() {
    $file_system = \Drupal::service('file_system');

    // Create a second image field.
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, $this->contentType->id());

    // Set second image field to hidden.
    $edit = [];
    $edit["fields[{$field_name}][type]"] = 'hidden';
    $this->drupalPostForm("admin/structure/types/manage/{$this->contentType->id()}/display", $edit, t('Save'));

    // Create node with two images attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit["files[{$this->field->get('field_name')}_0]"] = $file_system->realpath($test_image->uri);
    $edit["files[{$field_name}_0]"] = $file_system->realpath($test_image->uri);
    $this->drupalPostForm('node/add/' . $this->contentType->id(), $edit, t('Save and publish'));

    // Add Alt text.
    $edit = [];
    $edit["{$this->field->get('field_name')}[0][alt]"] = $this->randomString();
    $edit["{$field_name}[0][alt]"] = $this->randomString();
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Retrieve ID of the newly created node from the current URL.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Execute token_replace() with a Field token.
    $node = Node::load($nid);
    $token = "[node:{$this->field->get('field_name')}-formatted:0:image]";
    \Drupal::service('token')->replace($token, ['node' => $node]);
  }

}
