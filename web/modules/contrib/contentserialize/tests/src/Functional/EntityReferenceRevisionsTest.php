<?php

namespace Drupal\Tests\contentserialize\Functional;

use Drupal\contentserialize\Destination\FileDestination;
use Drupal\contentserialize\Source\FileSource;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Test exporting and importing revision references.
 *
 * @group contentserialize
 */
class EntityReferenceRevisionsTest extends BrowserTestBase {

  protected static $modules = ['contentserialize', 'node', 'entity_reference_revisions'];

  /**
   * Test a simple export and import with an entity reference revision field.
   */
  function testExportImport() {
    $this->drupalCreateContentType(['type' => 'article']);

    // Add the entity_reference_revisions field to article.
    // @todo It's not very realistic to have a node target type, but I dont see
    //   why that should be a problem. block_content might be a better choice.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_embedded',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => ['target_type' => 'node'],
    ]);
    $field_storage->save();
    FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ))->save();

    FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ])->save();

    // Create a target node that's referenced by a host node.
    $target = Node::create([
      'type' => 'article',
      'title' => 'Target',
      'body' => ['value' => 'Target Body', 'format' => 'basic_html'],
      'uid' => 1,
    ]);
    $target->save();

    $host = Node::create([
      'type' => 'article',
      'title' => 'Host',
      'body' => ['value' => 'Host Body', 'format' => 'basic_html'],
      'uid' => 1,
    ]);
    $host->field_embedded = $target;
    $host->save();

    // Export them.
    $destination = new FileDestination(file_directory_temp());
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $serialized = $exporter->exportMultiple([$host, $target], 'json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $destination->saveMultiple($serialized);

    // Delete them.
    $uuids = ['host' => $host->uuid(), 'target' => $target->uuid()];
    $host->delete();
    $target->delete();

    // Reimport them.
    /** @var \Drupal\contentserialize\ImporterInterface $importer */
    $importer = \Drupal::service('contentserialize.importer');
    $result = $importer->import(new FileSource(file_directory_temp()));
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['uuid' => array_values($uuids)]);
    foreach ($nodes as $node) {
      $nodes[$node->uuid()] = $node;
    }
    $host = $nodes[$uuids['host']];
    $target = $nodes[$uuids['target']];

    // Check them.
    $this->assertFalse($result->getFailures());

    $this->assertEquals($uuids['host'], $host->uuid());
    $this->assertEquals($uuids['target'], $target->uuid());

    $this->assertEquals('Host', $host->label());
    $this->assertEquals('Target', $target->label());

    $this->assertEquals('Host Body', $host->body->value);
    $this->assertEquals('Target Body', $target->body->value);

    $this->assertEquals($target->id(), $host->field_embedded->target_id);

    $this->assertEquals(1, $host->uid->target_id);
    $this->assertEquals(1, $target->uid->target_id);
  }
  
}