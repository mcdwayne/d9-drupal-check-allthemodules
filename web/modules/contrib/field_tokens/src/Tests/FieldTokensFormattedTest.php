<?php

namespace Drupal\field_tokens\Tests;

use Drupal\node\Entity\Node;

/**
 * Tests the Formatted field tokens.
 *
 * @group Field tokens
 */
class FieldTokensFormattedTest extends FieldTokensTestBase {

  /**
   * Test that Formatted tokens render correctly.
   */
  public function testFormattedTokens() {
    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $this->field->get('field_name'), $this->contentType->id(), $this->randomString());

    $node = Node::load($nid);
    $display = [
      'type'     => 'image',
      'settings' => [
        'image_style' => '',
        'image_link'  => '',
      ],
      'module'   => 'image',
    ];
    $element = $node->{$this->field->get('field_name')}->view($display);
    $output = \Drupal::service('renderer')->renderRoot($element['0']);

    // Image field with Image formatter.
    $token = "[node:{$this->field->get('field_name')}-formatted:0:image]";
    $value = \Drupal::service('token')->replace($token, ['node' => $node]);

    // Check the token is rendered correctly.
    $this->assertEqual($value, $output, $token . ' matches rendered Image formatter for provided Image field.');
  }

}
