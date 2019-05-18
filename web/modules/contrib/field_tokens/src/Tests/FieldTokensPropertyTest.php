<?php

namespace Drupal\field_tokens\Tests;

use Drupal\node\Entity\Node;

/**
 * Tests the Field property tokens.
 *
 * @group Field tokens
 */
class FieldTokensPropertyTest extends FieldTokensTestBase {

  /**
   * Test that Field property tokens render correctly.
   */
  public function testPropertyTokens() {
    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $this->field->get('field_name'), $this->contentType->id(), $this->randomString());
    $node = Node::load($nid);

    // Image field URI property token.
    $token = "[node:{$this->field->get('field_name')}-property:0:target_id]";
    $value = \Drupal::service('token')->replace($token, ['node' => $node]);

    // Check the token is rendered correctly.
    $this->assertEqual($value, $node->{$this->field->get('field_name')}[0]->target_id, $token . ' matches provided Image field target_id property.');
  }

}
