<?php

namespace Drupal\Tests\entity_usage\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Tests entity usage tracking on entity reference revision fields.
 *
 * THIS IS A LEGACY TEST AND SHOULD NOT BE FURTHER IMPROVED OR EXTENDED.
 *
 * @group entity_usage
 * @requires module entity_reference_revisions
 * @requires module paragraphs
 *
 * @package Drupal\Tests\entity_usage\Kernel
 */
class EntityUsageLegacyEntityReferenceRevisionsUsageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'entity_usage',
    'field',
    'file',
    'node',
    'paragraphs',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('entity_usage', ['entity_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests entity usage tracking on entity reference revision fields.
   */
  public function testEntityReferenceRevisionsTracking() {
    // Create a paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
    ]);
    $paragraph_type->save();

    // Add a title field to the paragraph bundle.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'title',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '1',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_text',
    ]);
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph = Paragraph::create([
      'title' => 'Simple paragraph',
      'type' => 'test_text',
    ]);
    $paragraph->save();

    // Create a node.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ]);
    $node->save();

    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');

    // First check usage is 0 for the referenced entity.
    $usage = $entity_usage->listSources($node);
    $this->assertSame([], $usage, 'Initial usage is correctly empty.');

    $node->set('node_paragraph_field', $paragraph);
    $node->save();

    // Add the paragraph to the node and check that the usage increases to 1.
    $usage = $entity_usage->listSources($paragraph);
    $this->assertEquals([
      'node' => [
        $node->id() => [
          0 => [
            'source_langcode' => $node->language()->getId(),
            'source_vid' => $node->getRevisionId() ?: 0,
            'method' => 'entity_reference',
            'field_name' => 'node_paragraph_field',
            'count' => 1,
          ],
        ],
      ],
    ], $usage, 'The usage count is correct.');

    // Remove the paragraph from the node and check the usage goes back to 0.
    $node->set('node_paragraph_field', NULL);
    $node->save();
    $usage = $entity_usage->listSources($node);
    $this->assertSame([], $usage, 'Non-referenced usage is correctly empty.');
  }

}
