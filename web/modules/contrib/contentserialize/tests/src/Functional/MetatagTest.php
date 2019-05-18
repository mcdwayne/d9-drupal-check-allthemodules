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
 * Class MetatagTest
 *
 * It currently needs the patch from #2922581.
 *
 * @package Drupal\Tests\contentserialize\Functional
 *
 * @group contentserialize
 *
 * @see https://www.drupal.org/node/2922581
 */
class MetatagTest extends BrowserTestBase {

  protected static $modules = ['contentserialize', 'node', 'metatag'];

  public function testExportImport() {
    $this->drupalCreateContentType(['type' => 'article']);

    FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_metatags',
      'entity_type' => 'node',
      'type' => 'metatag',
    ]);
    $field_storage->save();
    FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ))->save();

    $metatag_value = serialize(['title' => 'Custom Page Title']);
    $article = Node::create([
      'type' => 'article',
      'title' => 'Test Content',
      'body' => ['value' => 'Test Content Body', 'format' => 'basic_html'],
      'field_metatags' => ['value' => $metatag_value],
      'uid' => 1,
    ]);
    $article->save();

    // Export it.
    $destination = new FileDestination(file_directory_temp());
    /** @var \Drupal\contentserialize\ExporterInterface $exporter */
    $exporter = \Drupal::service('contentserialize.exporter');
    $serialized = $exporter->exportMultiple([$article], 'json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    $destination->saveMultiple($serialized);

    // Delete it.
    $uuid = $article->uuid();
    $article->delete();

    // Reimport it.
    /** @var \Drupal\contentserialize\ImporterInterface $importer */
    $importer = \Drupal::service('contentserialize.importer');
    $result = $importer->import(new FileSource(file_directory_temp()));

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = \Drupal::service('entity.repository');
    /** @var \Drupal\file\FileInterface $file */
    $article = $repository->loadEntityByUuid('node', $uuid);

    // Check it.
    $this->assertFalse($result->getFailures());
    $this->assertEquals($uuid, $article->uuid());
    $this->assertEquals($metatag_value, $article->field_metatags->value);
    $this->assertEquals('Test Content', $article->label());
    $this->assertEquals('Test Content Body', $article->body->value);
    $this->assertEquals(1, $article->uid->target_id);
  }

}