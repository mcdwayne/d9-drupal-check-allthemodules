<?php

namespace Drupal\Tests\field_default_token\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests that tokens in default values of node fields get replaced correctly.
 *
 * @group field_default_token
 */
class FieldDefaultTokenNodeTest extends FieldDefaultTokenKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  protected $bundle = 'article';

  /**
   * {@inheritdoc}
   *
   * DRUPAL_OPTIONAL is defined in system.module.
   */
  public static $modules = ['node', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');

    NodeType::create(['type' => $this->bundle])->save();
  }

  /**
   * Test that tokens in a field default value get replaced properly.
   */
  public function testReplacement() {
    $field = $this->createField();
    $field->setDefaultValue('This is the node title: [node:title]')->save();

    $node = Node::create([
      'type' => $this->bundle,
      'title' => 'Test node title',
    ]);

    // Make sure that the token is properly stripped before it can be replaced.
    $expected = [['value' => 'This is the node title: ']];
    $this->assertEquals($expected, $field->getDefaultValue($node));

    $node->save();

    $expected = [['value' => 'This is the node title: Test node title']];
    $this->assertEquals($expected, $field->getDefaultValue($node));
  }

}
