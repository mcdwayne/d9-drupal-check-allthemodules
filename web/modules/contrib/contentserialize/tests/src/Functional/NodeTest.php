<?php

namespace Drupal\Tests\contentserialize\Functional;

use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\Source\FileSource;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Test exporting and importing nodes.
 *
 * @group contentserialize
 */
class NodeTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

  protected static $modules = ['contentserialize', 'node'];

  /**
   * Test that entities referencing one another are imported correctly.
   */
  public function testCyclicReferences() {
    $this->drupalCreateContentType(['type' => 'article']);
    $this->createEntityReferenceField('node', 'article', 'field_related_content', 'Related content', 'node');

    FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ])->save();

    // Create two nodes A and B that refer to each other.
    $a = Node::create([
      'type' => 'article',
      'title' => 'Test Content A',
      'body' => ['value' => 'Test Content A Body', 'format' => 'basic_html'],
      'uid' => 1,
    ]);
    $a->save();

    $b = Node::create([
      'type' => 'article',
      'title' => 'Test Content B',
      'body' => ['value' => 'Test Content B Body', 'format' => 'basic_html'],
      'uid' => 1,
    ]);
    // Is there a simpler way to set this, eg. just assignment of the entity?
    $b->field_related_content->target_id = $a->id();
    $b->save();

    $a->field_related_content->target_id = $b->id();
    $a->save();

    // Export them.
    $destination = new FileDestination(file_directory_temp());
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $serialized = $exporter->exportMultiple([$a, $b], 'json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $destination->saveMultiple($serialized);

    // Delete them.
    $uuids = ['a' => $a->uuid(), 'b' => $b->uuid()];
    $a->delete();
    $b->delete();

    // Reimport them.
    /** @var \Drupal\contentserialize\ImporterInterface $importer */
    $importer = \Drupal::service('contentserialize.importer');
    $result = $importer->import(new FileSource(file_directory_temp()));
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['uuid' => array_values($uuids)]);
    foreach ($nodes as $node) {
      $nodes[$node->uuid()] = $node;
    }
    $a = $nodes[$uuids['a']];
    $b = $nodes[$uuids['b']];

    // Check them.
    $this->assertFalse($result->getFailures());

    $this->assertEquals($uuids['a'], $a->uuid());
    $this->assertEquals($uuids['b'], $b->uuid());

    $this->assertEquals('Test Content A', $a->label());
    $this->assertEquals('Test Content B', $b->label());

    $this->assertEquals('Test Content A Body', $a->body->value);
    $this->assertEquals('Test Content B Body', $b->body->value);

    $this->assertEquals($a->id(), $b->field_related_content->target_id);
    $this->assertEquals($b->id(), $a->field_related_content->target_id);

    $this->assertEquals(1, $a->uid->target_id);
    $this->assertEquals(1, $b->uid->target_id);
  }

}